<?php

namespace App\Services;

use App\Models\CatalogueReference;
use Illuminate\Support\Facades\Schema;

/**
 * Looks a book up in the local reference catalogue (~81k almouggar.com rows) and
 * returns it in the SAME normalized shape as the web sources in
 * BookIngestionService, so it can be treated as just another enrichment source.
 *
 * Matching, best-effort in order: exact ISBN → exact title → normalized title
 * (optionally narrowed by author). Ties are broken by `completeness`.
 */
class CatalogueLookupService
{
    /** Map the catalogue's `langue` to our language codes. */
    private const LANG_MAP = [
        'arabe'    => 'arabic',
        'français' => 'french',
        'francais' => 'french',
        'anglais'  => 'english',
        'espagnol' => 'spanish',
        'allemand' => 'german',
    ];

    /**
     * @return array|null Normalized book data, or null if nothing matched / the
     *                    reference table isn't present.
     */
    public function lookup(?string $isbn, ?string $title, ?string $author = null): ?array
    {
        if (!Schema::hasTable('catalogue_reference')) {
            return null;
        }

        $match = null;

        // 1) ISBN is the strongest signal.
        $cleanIsbn = $this->cleanIsbn($isbn);
        if ($cleanIsbn) {
            $match = CatalogueReference::where('isbn', $cleanIsbn)
                ->orderByDesc('completeness')
                ->first();
        }

        $title = trim((string) $title);
        if (!$match && $title !== '') {
            // 2) Exact title.
            $q = CatalogueReference::where('title', $title);
            $this->narrowByAuthor($q, $author);
            $match = $q->orderByDesc('completeness')->first();

            // 3) Normalized title.
            if (!$match) {
                $norm = $this->normalize($title);
                if ($norm !== '') {
                    $q = CatalogueReference::where('title_normalized', $norm);
                    $this->narrowByAuthor($q, $author);
                    $match = $q->orderByDesc('completeness')->first();
                }
            }
        }

        return $match ? $this->toNormalized($match) : null;
    }

    /** Prefer rows whose author also matches, but don't require it. */
    private function narrowByAuthor($query, ?string $author): void
    {
        $author = trim((string) $author);
        if ($author === '') {
            return;
        }
        $normAuthor = $this->normalize($author);
        // Only narrow if it doesn't eliminate every candidate — checked by the
        // caller via a clone would be heavier; instead we OR-in a permissive
        // author filter and rely on completeness ordering. Keep it simple: filter
        // only when at least one row matches.
        $narrowed = (clone $query)->where('author_normalized', $normAuthor);
        if ($narrowed->exists()) {
            $query->where('author_normalized', $normAuthor);
        }
    }

    /** Map a catalogue row to the shared normalized source shape. */
    private function toNormalized(CatalogueReference $c): array
    {
        $pages = (int) preg_replace('/\D/', '', (string) $c->pages);

        return [
            'title'       => $c->title,
            'author'      => $c->author,
            'description' => $c->description ? trim($c->description) : null,
            'isbn'        => $c->isbn,
            'page_num'    => $pages > 0 ? $pages : 0,
            'publisher'   => $c->edition ? trim($c->edition) : null, // `edition` holds the publisher
            'language'    => $this->mapLanguage($c->langue),
            'categories'  => [], // source category is noise ("Produits"), ignore
            'image_url'   => $c->coverUrl(),
        ];
    }

    /** 'Arabe / Français' → first recognized code ('arabic'); null if none. */
    private function mapLanguage(?string $langue): ?string
    {
        if (!$langue) {
            return null;
        }
        foreach (preg_split('/[\/,]/', $langue) as $part) {
            $key = mb_strtolower(trim($part));
            if (isset(self::LANG_MAP[$key])) {
                return self::LANG_MAP[$key];
            }
        }
        return null;
    }

    /**
     * Approximate the dump's own normalization: lowercase, strip Latin accents,
     * fold Arabic letter variants, drop punctuation/diacritics, collapse spaces.
     */
    private function normalize(string $s): string
    {
        $s = mb_strtolower(trim($s));

        // Latin accents.
        $s = strtr($s, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ñ' => 'n',
        ]);

        // Arabic letter folding.
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);
        // Arabic diacritics (tashkeel).
        $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $s);

        // Drop anything that isn't a letter, number or space; collapse spaces.
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    /** Normalize an ISBN to 10/13 chars, or null. */
    private function cleanIsbn(?string $isbn): ?string
    {
        if (empty($isbn)) {
            return null;
        }
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        return in_array(strlen($clean), [10, 13], true) ? $clean : null;
    }
}
