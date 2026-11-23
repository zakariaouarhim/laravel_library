<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\PublishingHouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared "create a real Book from a reviewed import row" logic, used by both the
 * reader-import and catalogue-import admin tools. Handles author/publisher
 * firstOrCreate, cover resolution (already-processed webp / local file / remote
 * URL), multi-category sync, and the AI-rewrite bookkeeping — identically for
 * every source so the two tools can't drift.
 */
class BookImportService
{
    public function __construct(private ImageService $imageService)
    {
    }

    /**
     * Clean an ISBN coming from an API/enrich source into a value that fits the
     * books.isbn column (varchar 17): keep digits + X, drop hyphens/spaces/notes,
     * and cap the length. Returns null when nothing usable remains. Prevents the
     * "max:20" validation failure when a source returns a long/multi-value ISBN.
     */
    public static function normalizeIsbn(?string $isbn): ?string
    {
        if ($isbn === null || trim($isbn) === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9Xx]/', '', $isbn);

        return $clean === '' ? null : strtoupper(substr($clean, 0, 17));
    }

    /**
     * @param array $data     Validated fields: name, author?, isbn?, page_num?,
     *                         publisher?, language, price, description?, quantity,
     *                         category_ids[], primary_category_id.
     * @param array $cover     One of: ['webp' => public path] (already processed),
     *                         ['file' => absolute local path], ['url' => remote url];
     *                         plus 'prefix' for the generated filename.
     * @param array $rewrite   ['rewritten' => bool, 'original_description' => ?string].
     * @param callable|null $afterCreate  Runs inside the same transaction with the
     *                         new Book, e.g. to flip the source row to "imported".
     */
    public function create(array $data, array $cover = [], array $rewrite = [], ?callable $afterCreate = null): Book
    {
        // Keep model events (slug, observers) but don't hard-depend on Meilisearch
        // during the multi-step create: the intermediate saves shouldn't each hit
        // the index. We index once, after commit, below.
        // By reference, so the post-commit enrichment below knows whether this
        // import actually created a new author. A normal closure, not an arrow
        // fn: arrow functions capture by value and the reference wouldn't carry.
        $newAuthorId = null;

        $book = Book::withoutSyncingToSearch(function () use ($data, $cover, $rewrite, $afterCreate, &$newAuthorId) {
            return DB::transaction(function () use ($data, $cover, $rewrite, $afterCreate, &$newAuthorId) {
            // An author_id means the admin picked an existing author from the
            // autocomplete, which is the only thing that reliably prevents a
            // spelling variant creating a second Author row — firstOrCreate
            // below matches on the exact string. The name must still agree, so
            // a stale binding (picked, then edited) falls back to the name.
            $authorId   = null;
            $authorName = trim((string) ($data['author'] ?? ''));
            if ($authorName !== '' && !empty($data['author_id'])) {
                $bound = Author::find($data['author_id']);
                if ($bound && mb_strtolower(trim($bound->name)) === mb_strtolower($authorName)) {
                    $authorId = $bound->id;
                } else {
                    Log::info("BookImportService: dropped author binding #{$data['author_id']} for '{$authorName}' (name mismatch)");
                }
            }
            if (!$authorId && $authorName !== '') {
                $author   = Author::firstOrCreate(['name' => $authorName], ['status' => 'active']);
                $authorId = $author->id;
                if ($author->wasRecentlyCreated) {
                    $newAuthorId = $author->id;
                }
            }

            $publisherId = null;
            if (!empty($data['publisher'])) {
                $publisherId = PublishingHouse::firstOrCreate(
                    ['name' => trim($data['publisher'])],
                    ['status' => 'active']
                )->id;
            }

            $imagePath = $this->resolveCover($cover);

            $book = Book::create([
                'title'               => $data['name'],
                'type'                => 'book',
                'product_type'        => 'standard',
                'author_id'           => $authorId,
                'description'         => ($data['description'] ?? null) ?: null,
                'price'               => $data['price'],
                'discount'            => 0,
                'category_id'         => $data['primary_category_id'],
                'image'               => $imagePath,
                'page_num'            => $data['page_num'] ?? 0,
                'language'            => $data['language'],
                'publishing_house_id' => $publisherId,
                // Series: both columns are nullable and the review screens send
                // them only when the admin picks a series, so ?? null both
                // leaves a standalone book untouched and lets "no series" clear.
                'series_id'           => $data['series_id'] ?? null,
                'volume_number'       => $data['volume_number'] ?? null,
                'isbn'                => ($data['isbn'] ?? null) ?: null,
                'quantity'            => (int) $data['quantity'],
                'api_data_status'     => 'pending',
                'status'              => 'active',
            ]);

            // Preserve the pre-rewrite text and mark it so the nightly rewrite cron
            // skips it. These columns aren't in Book::$fillable — set via forceFill.
            if (!empty($rewrite['rewritten'])) {
                $book->forceFill([
                    'original_description' => $rewrite['original_description'] ?? null,
                    'rewrite_status'       => 'rewritten',
                    'rewritten_at'         => now(),
                ])->save();
            }

            if ($authorId) {
                $book->authors()->syncWithoutDetaching([$authorId => ['author_type' => 'primary']]);
            }
            $book->syncCategories($data['category_ids'], $data['primary_category_id']);

            if ($afterCreate) {
                $afterCreate($book);
            }

            return $book;
            });
        });

        // A brand-new author starts as just a name. Enrich it (bio, avatar,
        // nationality) the way the retired API-ingest flow did — but AFTER the
        // commit, not inside the transaction as that one did: enrichAuthor()
        // makes external HTTP calls, which must never hold a transaction open.
        if ($newAuthorId) {
            try {
                app(AuthorEnrichmentService::class)->enrichAuthor(Author::find($newAuthorId));
            } catch (\Throwable $e) {
                Log::warning("BookImportService: author enrichment failed for #{$newAuthorId}: " . $e->getMessage());
            }
        }

        // Index the freshly-created book so public (Meilisearch) search finds it
        // immediately — without this, imported books show in the admin (DB) but
        // never in site search. Wrapped so a Meilisearch outage degrades to the
        // old behaviour (book still created; recoverable via `scout:import`).
        try {
            $book->searchable();
        } catch (\Throwable $e) {
            \Log::warning("BookImportService: failed to index book {$book->id} to search: " . $e->getMessage());
        }

        return $book;
    }

    /**
     * Did this import ask for a cover and end up without one? resolveCover()
     * returns null when the download or the webp conversion fails, and the book
     * is still created — so callers use this to warn the admin rather than
     * report a clean success and leave an image-less book behind. Keyed off the
     * same $cover shape resolveCover() accepts, so the two stay in step.
     */
    public static function coverMissing(Book $book, array $cover): bool
    {
        $requested = !empty($cover['webp']) || !empty($cover['file']) || !empty($cover['url']);

        return $requested && empty($book->image);
    }

    /** Resolve the cover to a public path, or null. */
    private function resolveCover(array $cover): ?string
    {
        $prefix = $cover['prefix'] ?? 'import';
        // Admin-chosen zoom (center crop, independent per axis) from the
        // review modal; 1.0 = as-is.
        $zoomW = (float) ($cover['zoom_w'] ?? 1.0);
        $zoomH = (float) ($cover['zoom_h'] ?? 1.0);
        $zoomed = $zoomW > 1.01 || $zoomH > 1.01;

        // Already-processed webp sitting in public/ (admin-replaced cover).
        if (!empty($cover['webp']) && is_file(public_path($cover['webp']))) {
            // Zoomed: re-process the file so the crop (and its thumb/large
            // variants) are regenerated; un-zoomed keeps the path as-is.
            if ($zoomed) {
                return $this->imageService->processLocalFile(public_path($cover['webp']), 'images/books', $prefix, $zoomW, $zoomH)
                    ?? $cover['webp'];
            }

            return $cover['webp'];
        }
        // A local source file to process (webp + thumb + large).
        if (!empty($cover['file']) && is_file($cover['file'])) {
            return $this->imageService->processLocalFile($cover['file'], 'images/books', $prefix, $zoomW, $zoomH);
        }
        // A remote cover URL to download + process.
        if (!empty($cover['url'])) {
            return $this->imageService->downloadFromUrl($cover['url'], 'images/books', $prefix, $zoomW, $zoomH);
        }

        return null;
    }
}
