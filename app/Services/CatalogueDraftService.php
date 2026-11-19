<?php

namespace App\Services;

use App\Models\CatalogueReference;
use Illuminate\Support\Facades\DB;

/**
 * Turns an API-found book into a `catalogue_reference` row tagged
 * `dedup_key = "api:…"`.
 *
 * Why a catalogue row and not a separate staging table: once the book is a
 * catalogue row, the whole existing review surface — card grid, detail modal,
 * enrich panel, category tree, cover zoom, approve/skip/unskip — works on it
 * unchanged. It matters for the cover specifically: approve() accepts no
 * cover_url input, so a remote cover can only reach it via the row.
 *
 * The prefix is how the review dashboard tells sources apart; there is
 * deliberately no `source` column (catalogue:import drop/recreates the table,
 * so an added column wouldn't survive — see ImportCatalogueReference, which
 * preserves `api:` rows across that rebuild because they are admin work).
 *
 * Shared by the catalogue-import "find this book" panel, the one-time
 * pending_books conversion, and the `books:ingest` CSV command.
 */
class CatalogueDraftService
{
    /** Same precedence EnrichPreviewService uses, so a draft matches the panel. */
    private const PRIORITY = ['catalogue', 'bnf', 'google_books', 'open_library', 'wikipedia'];

    /** our language code -> the `langue` token the dashboard filters on. */
    public const LANGUE_TOKEN = [
        'arabic' => 'Arabe', 'french' => 'Français', 'english' => 'Anglais',
        'spanish' => 'Espagnol', 'german' => 'Allemand',
    ];

    /** The plain fields a draft carries between the panel and this service. */
    public const FIELDS = ['title', 'author', 'isbn', 'description', 'publisher', 'pages', 'cover_url', 'category'];

    /**
     * Merge resolved API sources into one plain draft, taking the first
     * non-empty value per field in PRIORITY order. The admin refines individual
     * fields afterwards with the modal's "إثراء من API" panel, which shows every
     * source's value — so this only needs to be a sensible first draft.
     *
     * @param array<string,array> $sources source => normalized book data, as
     *        returned by BookIngestionService::resolveSources(ByIsbn).
     * @return array{title:string,author:?string,isbn:?string,description:?string,publisher:?string,pages:?int,cover_url:?string,category:?string}
     */
    public function mergeSources(array $sources, string $title = '', string $author = ''): array
    {
        // Prefer a source's title/author: on an ISBN lookup the caller has no
        // real title, and sources carry canonical spelling and diacritics.
        $pages = (int) ($this->pick($sources, 'page_num') ?? 0);

        return [
            'title'       => trim((string) ($this->pick($sources, 'title') ?? '')) ?: trim($title),
            'author'      => $this->cleanAuthor($this->pick($sources, 'author')) ?? trim($author),
            'isbn'        => $this->pick($sources, 'isbn'),
            'description' => $this->pick($sources, 'description'),
            'publisher'   => $this->pick($sources, 'publisher'),
            'pages'       => $pages > 0 ? $pages : null,
            'cover_url'   => $this->pick($sources, 'image_url'),
            'category'    => $this->subjects($sources),
        ];
    }

    /**
     * Build + persist the `api:` row for one draft, then return the fresh model.
     * Idempotent: the dedup_key is derived from the book's identity, so adopting
     * the same book twice refreshes one row instead of creating a second.
     */
    public function adopt(array $fields, string $language): CatalogueReference
    {
        $row = $this->assemble($fields, $language);

        // Query builder on purpose: CatalogueReference is read-only by
        // convention ($timestamps = false).
        DB::table('catalogue_reference')->upsert(
            [$row],
            ['dedup_key'],
            array_values(array_diff(array_keys($row), ['dedup_key', 'created_at']))
        );

        return CatalogueReference::where('dedup_key', $row['dedup_key'])->firstOrFail();
    }

    /** mergeSources() + adopt() in one call, for the bulk/conversion commands. */
    public function adoptSources(array $sources, string $title, string $author, string $language): CatalogueReference
    {
        return $this->adopt($this->mergeSources($sources, $title, $author), $language);
    }

    /** One plain draft -> one catalogue_reference row array. */
    public function assemble(array $fields, string $language): array
    {
        $language = isset(self::LANGUE_TOKEN[$language]) ? $language : 'arabic';

        $title  = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($fields['title'] ?? ''))), 0, 191);
        $author = trim(preg_replace('/\s+/u', ' ', (string) ($fields['author'] ?? '')));
        $author = $author !== '' ? mb_substr($author, 0, 191) : null;

        if ($title === '') {
            throw new \InvalidArgumentException('A draft needs a title.');
        }

        $isbn        = $this->cleanIsbn($fields['isbn'] ?? null);
        $description = trim(strip_tags((string) ($fields['description'] ?? '')));
        $publisher   = trim((string) ($fields['publisher'] ?? ''));
        $category    = trim((string) ($fields['category'] ?? ''));
        $pages       = (int) ($fields['pages'] ?? 0);
        $cover       = trim((string) ($fields['cover_url'] ?? ''));
        $now         = now();

        $row = [
            'dedup_key'         => $this->dedupKey($title, $author, $isbn, $language),
            'scraped_book_id'   => null, // nullable: these rows have no source id
            'isbn'              => $isbn,
            'title'             => $title,
            'title_normalized'  => mb_substr(CatalogueLookupService::normalize($title), 0, 191),
            'author'            => $author,
            'author_normalized' => $author ? mb_substr(CatalogueLookupService::normalize($author), 0, 191) : null,
            'description'       => $description !== '' ? $description : null,
            'price'             => null, // the admin sets the retail price at review
            'cover_url'         => $cover !== '' ? $cover : null,
            'category'          => $category !== '' ? mb_substr($category, 0, 191) : null,
            'edition'           => $publisher !== '' ? mb_substr($publisher, 0, 191) : null,
            'langue'            => self::LANGUE_TOKEN[$language],
            'pages'             => $pages > 0 ? (string) $pages : null,
            'dimensions'        => null,
            'source_url'        => null, // the normalized source shape carries no canonical page url
            'completeness'      => 0,
            'source_last_seen'  => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
        $row['completeness'] = $this->completeness($row);

        return $row;
    }

    /** First non-empty scalar for $field across the sources, in PRIORITY order. */
    private function pick(array $sources, string $field)
    {
        $ordered = array_merge(
            self::PRIORITY,
            // Defensive: an unexpected source key shouldn't be silently ignored.
            array_values(array_diff(array_keys($sources), self::PRIORITY))
        );

        foreach ($ordered as $src) {
            $value = $sources[$src][$field] ?? null;
            if (is_array($value) || $value === null || $value === '' || $value === 0) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * Library catalogues return authors with cataloguing cruft attached — BNF
     * gives "Camus, Albert (1913-1960). Auteur du texte / Autrice du texte".
     * Left alone it becomes the book's author and creates a junk Author row at
     * approve time, so strip the cruft and un-invert the name.
     */
    private function cleanAuthor($raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $a = $raw;
        // Life dates: "(1913-1960)", "(1913-)", "(1913)".
        $a = preg_replace('/\(\s*\d{3,4}\s*-?\s*\d{0,4}\s*\)/u', ' ', $a);
        // Role suffixes. Matched by name, NOT by splitting on ". " — that would
        // cut "J. K. Rowling" down to "J".
        $a = preg_replace(
            '/\.\s*(Auteur|Autrice|[ÉE]diteur|[ÉE]ditrice|Traduct(?:eur|rice)|Illustrat(?:eur|rice)|Pr[ée]facier|Compilat(?:eur|rice)|Author|Editor|Translator|Illustrator)\b.*$/ui',
            '',
            $a
        );
        // Several contributors in one string -> keep the first, as Google Books
        // already does by taking authors[0].
        $a = preg_split('/\s*\/\s*/u', $a)[0] ?? $a;
        $a = trim(preg_replace('/\s+/u', ' ', $a));
        $a = rtrim($a, " \t\n\r\0\x0B,;:-");
        // A trailing "." is cruft — unless it belongs to an initial ("Rowling, J. K.").
        if (!preg_match('/\p{Lu}\.$/u', $a)) {
            $a = rtrim($a, '.');
        }
        $a = ltrim($a, " \t\n\r\0\x0B,.;:-");
        // "Camus, Albert" -> "Albert Camus". Latin script only: the convention
        // doesn't apply to Arabic names, which are stored as written.
        if (!preg_match('/\p{Arabic}/u', $a) && preg_match('/^([^,]{2,}),\s*([^,]{2,})$/u', $a, $m)) {
            $a = trim($m[2]) . ' ' . trim($m[1]);
        }

        return $a !== '' ? $a : null;
    }

    /** Deduped subject/genre hint merged across every source, or null. */
    private function subjects(array $sources): ?string
    {
        $seen = [];
        foreach ($sources as $data) {
            foreach ((array) ($data['categories'] ?? []) as $s) {
                $s = trim((string) $s);
                if ($s !== '') {
                    $seen[mb_strtolower($s)] = $s; // dedupe case-insensitively, keep original case
                }
            }
        }

        return $seen === [] ? null : mb_substr(implode(' / ', array_values($seen)), 0, 191);
    }

    /**
     * Stable identity for the row. ISBN when we have one (the strongest key);
     * otherwise a hash of title+author+language, so re-searching the same book
     * lands on the same row instead of piling up duplicates.
     */
    private function dedupKey(string $title, ?string $author, ?string $isbn, string $language): string
    {
        if ($isbn !== null) {
            return 'api:isbn:' . $isbn;
        }

        return 'api:' . sha1(mb_strtolower($title) . '|' . mb_strtolower((string) $author) . '|' . $language);
    }

    /** Checksum-validated 13/10-char ISBN, or null. */
    private function cleanIsbn($raw): ?string
    {
        if (!is_string($raw) && !is_numeric($raw)) {
            return null;
        }
        if (!preg_match('/(97[89]\d{10}|\d{9}[\dX])/i', str_replace(['-', ' '], '', (string) $raw), $m)) {
            return null;
        }
        $isbn = strtoupper($m[1]);

        // Placeholder ISBNs ("0000000000") pass both checksums, and because the
        // ISBN becomes this row's dedup_key they would merge unrelated books
        // into one row — and falsely match books in the store by ISBN.
        if (preg_match('/^(\d)\1+$/', $isbn)) {
            return null;
        }

        return (strlen($isbn) === 13 && $this->isbn13Valid($isbn)) || strlen($isbn) === 10 ? $isbn : null;
    }

    private function isbn13Valid(string $isbn): bool
    {
        $check = 0;
        foreach (str_split($isbn) as $i => $d) {
            $check += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return $check % 10 === 0;
    }

    /** Honest 0-8 score on the same fields the scraped sources are scored on. */
    private function completeness(array $row): int
    {
        $fields = ['title', 'author', 'isbn', 'description', 'price', 'cover_url', 'category', 'langue'];

        return count(array_filter($fields, fn ($f) => !empty($row[$f])));
    }
}
