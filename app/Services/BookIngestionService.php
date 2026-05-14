<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookAuthor;
use App\Models\PendingBook;
use App\Models\PublishingHouse;
use App\Services\AuthorEnrichmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookIngestionService
{
    /**
     * Map ISO 639-1 language codes returned by Google Books / Open Library
     * onto the canonical Book::LANGUAGES values.
     */
    private const LANGUAGE_MAP = [
        'fr' => 'french',
        'en' => 'english',
        'ar' => 'arabic',
        'es' => 'spanish',
        'de' => 'german',
    ];

    public function __construct(
        private ImageService $images,
    ) {}

    /**
     * Stage a (title, author) pair: look up Google Books then Open Library,
     * download a cover into staging, save to pending_books.
     *
     * Idempotent: if a non-finalized pending row already exists for the same
     * (title, author, language), returns it without re-querying any API.
     */
    public function stageFromTitleAuthor(string $title, string $author, string $language = 'french', bool $force = false, ?int $authorId = null): PendingBook
    {
        $title  = trim($title);
        $author = trim($author);

        // Idempotency: reuse an in-flight pending row if one already exists.
        $existing = PendingBook::where('title', $title)
            ->where('author_name', $author)
            ->where('language', $language)
            ->whereIn('status', [
                PendingBook::STATUS_ENRICHED,
                PendingBook::STATUS_FAILED,
                PendingBook::STATUS_DUPLICATE,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        // Run every applicable source — never short-circuit. Admin compares them
        // side-by-side on the review page and picks per field. BNF is only useful
        // for French; Wikipedia is queried in the book's own language.
        $sources = $language === 'french'
            ? ['bnf', 'google_books', 'open_library', 'wikipedia']
            : ['google_books', 'open_library', 'wikipedia'];

        $t0 = microtime(true);
        $apiResults    = [];   // [source => normalized array]
        $sourcesToFetch = [];  // sources not in cache
        $cacheHits     = [];   // names of sources served from cache (for logging)

        // Cache lookup pass — every source has a 24h cache keyed by (source, title, author, language).
        // Values are wrapped in ['v' => $parsed] because Laravel's Cache::get() treats stored null
        // as a miss; wrapping ensures the cached entry is never literally null and "API returned
        // nothing" can be persisted to skip retrying.
        foreach ($sources as $source) {
            if ($force) {
                $sourcesToFetch[] = $source;
                continue;
            }
            $cached = Cache::get($this->ingestCacheKey($source, $title, $author, $language));
            if ($cached === null) {
                $sourcesToFetch[] = $source;
            } else {
                $cacheHits[] = $source;
                $value = $cached['v'] ?? null;
                if (is_array($value)) $apiResults[$source] = $value;
            }
        }

        $responses = [];
        if (!empty($sourcesToFetch)) {
            // Phase 1: fire all cache-miss requests concurrently.
            $responses = $this->poolTitleAuthorRequests($sourcesToFetch, $title, $author, $language);
        }

        $t1 = microtime(true);
        Log::debug(sprintf(
            "BookIngestion timing — phase1 API pool: %.2fs (cache hits: %s | fetched: %s)",
            $t1 - $t0,
            $cacheHits ? implode(',', $cacheHits) : 'none',
            $sourcesToFetch ? implode(',', $sourcesToFetch) : 'none'
        ));

        foreach (['bnf', 'google_books', 'open_library'] as $source) {
            if (!in_array($source, $sourcesToFetch, true)) continue;
            try {
                $response = $responses[$source] ?? null;
                $parsed = null;
                if ($this->isOkResponse($response)) {
                    $parsed = match ($source) {
                        'bnf'          => $this->parseBnfResponse($response, $title),
                        'google_books' => $this->parseGoogleBooksResponse($response),
                        'open_library' => $this->parseOpenLibraryResponse($response, $title),
                    };
                }
                Cache::put($this->ingestCacheKey($source, $title, $author, $language), ['v' => $parsed], now()->addHours(24));
                if ($parsed) $apiResults[$source] = $parsed;
            } catch (\Throwable $e) {
                Log::info("BookIngestion {$source} parse failed for '{$title}': " . $e->getMessage());
            }
        }

        $t2 = microtime(true);
        Log::debug(sprintf("BookIngestion timing — phase1 parse: %.2fs (sources hit: %s)", $t2 - $t1, implode(',', array_keys($apiResults))));

        // Per-source HTTP transfer times — surfaces which API is the slowest.
        foreach ($responses as $key => $resp) {
            if ($resp instanceof \Illuminate\Http\Client\Response) {
                $stats = $resp->transferStats;
                $time  = $stats ? $stats->getTransferTime() : null;
                Log::debug(sprintf("BookIngestion timing —   %s: HTTP %d in %.2fs", $key, $resp->status(), $time ?? 0));
            } else {
                Log::debug(sprintf("BookIngestion timing —   %s: connection failed (%s)", $key, get_class($resp)));
            }
        }

        // Phase 2: Wikipedia's second hop depends on opensearch result, so it runs
        // sequentially after the pool. Cached separately under a final 'wikipedia' key
        // because the cached value already represents the merged 2-hop result.
        if (in_array('wikipedia', $sourcesToFetch, true)) {
            try {
                $searchResp = $responses['wikipedia'] ?? null;
                $parsed = null;
                if ($this->isOkResponse($searchResp)) {
                    $parsed = $this->finishWikipediaLookup($searchResp, $title, $language);
                }
                Cache::put($this->ingestCacheKey('wikipedia', $title, $author, $language), ['v' => $parsed], now()->addHours(24));
                if ($parsed) $apiResults['wikipedia'] = $parsed;
            } catch (\Throwable $e) {
                Log::info("BookIngestion wikipedia lookup failed for '{$title}': " . $e->getMessage());
            }
        }

        $t3 = microtime(true);
        Log::debug(sprintf("BookIngestion timing — phase2 wikipedia: %.2fs", $t3 - $t2));

        // Phase 3: stage cover images. Network fetches are pooled (concurrent);
        // WebP encoding + thumbnail generation happens sequentially after.
        // Without the pool this loop is ~3-4s per source × 4 sources = 12s+;
        // pooling drops it to ~max-single-fetch + CPU time (~2-3s total).
        $stagingImages = $this->poolStageImages($apiResults, $title, $author);

        $t4 = microtime(true);
        Log::debug(sprintf(
            "BookIngestion timing — phase3 images: %.2fs (covers saved: %d) | TOTAL: %.2fs for '%s'",
            $t4 - $t3, count($stagingImages), $t4 - $t0, $title
        ));

        // Duplicate detection: first try ISBN match (cheap + exact).
        $existingBookId = null;
        foreach ($apiResults as $r) {
            if (!empty($r['isbn']) && ($id = Book::where('isbn', $r['isbn'])->value('id'))) {
                $existingBookId = $id;
                break;
            }
        }

        // Fallback: fuzzy title+author match. Arabic books often have no ISBN,
        // so we need a non-ISBN path to catch dupes there.
        if (!$existingBookId) {
            $existingBookId = $this->findFuzzyDuplicate($title, $author);
        }

        $status = $existingBookId
            ? PendingBook::STATUS_DUPLICATE
            : (!empty($apiResults) ? PendingBook::STATUS_ENRICHED : PendingBook::STATUS_FAILED);

        return PendingBook::create([
            'title'            => $title,
            'author_name'      => $author,
            'author_id'        => $authorId,
            'language'         => $language,
            'status'           => $status,
            'api_results'      => $apiResults,
            'staging_images'   => $stagingImages,
            'error_message'    => empty($apiResults) ? 'لم يتم العثور على نتائج في أي مصدر' : null,
            'existing_book_id' => $existingBookId,
        ]);
    }

    /**
     * Stage a row by ISBN only — admin has the physical book in hand, just types
     * the ISBN. Every applicable API supports ISBN-based lookup which is much
     * more accurate than title+author guessing. Wikipedia is skipped (its ISBN
     * search is unreliable).
     */
    public function stageFromIsbn(string $isbn, string $language = 'french', bool $force = false): PendingBook
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (strlen($cleanIsbn) < 10) {
            throw new \InvalidArgumentException("ISBN غير صالح: {$isbn}");
        }

        $existing = PendingBook::where('title', 'ISBN: ' . $cleanIsbn)
            ->where('language', $language)
            ->whereIn('status', [
                PendingBook::STATUS_ENRICHED,
                PendingBook::STATUS_FAILED,
                PendingBook::STATUS_DUPLICATE,
            ])
            ->first();
        if ($existing) return $existing;

        $sources = $language === 'french'
            ? ['bnf', 'google_books', 'open_library']
            : ['google_books', 'open_library'];

        $t0 = microtime(true);
        $apiResults     = [];
        $sourcesToFetch = [];
        $cacheHits      = [];

        foreach ($sources as $source) {
            if ($force) { $sourcesToFetch[] = $source; continue; }
            $cached = Cache::get($this->ingestIsbnCacheKey($source, $cleanIsbn));
            if ($cached === null) {
                $sourcesToFetch[] = $source;
            } else {
                $cacheHits[] = $source;
                $value = $cached['v'] ?? null;
                if (is_array($value)) $apiResults[$source] = $value;
            }
        }

        $responses = [];
        if (!empty($sourcesToFetch)) {
            $responses = $this->poolIsbnRequests($sourcesToFetch, $cleanIsbn);
        }

        Log::debug(sprintf(
            "BookIngestion ISBN '%s' — phase1 API pool: %.2fs (cache: %s | fetched: %s)",
            $cleanIsbn,
            microtime(true) - $t0,
            $cacheHits ? implode(',', $cacheHits) : 'none',
            $sourcesToFetch ? implode(',', $sourcesToFetch) : 'none'
        ));

        foreach ($sourcesToFetch as $source) {
            try {
                $response = $responses[$source] ?? null;
                $parsed = null;
                if ($this->isOkResponse($response)) {
                    $parsed = match ($source) {
                        'bnf'          => $this->parseBnfIsbnResponse($response),
                        'google_books' => $this->parseGoogleBooksResponse($response),
                        'open_library' => $this->parseOpenLibraryIsbnResponse($response, $cleanIsbn),
                    };
                }
                Cache::put($this->ingestIsbnCacheKey($source, $cleanIsbn), ['v' => $parsed], now()->addHours(24));
                if ($parsed) $apiResults[$source] = $parsed;
            } catch (\Throwable $e) {
                Log::info("BookIngestion ISBN {$source} parse failed for '{$cleanIsbn}': " . $e->getMessage());
            }
        }

        // Pick title/author from the API results — prefer BNF > Google Books > Open Library.
        $title  = '';
        $author = '';
        foreach (['bnf', 'google_books', 'open_library'] as $src) {
            if (!isset($apiResults[$src])) continue;
            if ($title === '' && !empty($apiResults[$src]['title']))   $title  = $apiResults[$src]['title'];
            if ($author === '' && !empty($apiResults[$src]['author'])) $author = $apiResults[$src]['author'];
            if ($title !== '' && $author !== '') break;
        }

        $stagingImages = $this->poolStageImages($apiResults, $title ?: $cleanIsbn, $author ?: 'isbn');

        Log::debug(sprintf(
            "BookIngestion ISBN '%s' — TOTAL: %.2fs (sources: %s, covers: %d)",
            $cleanIsbn, microtime(true) - $t0, implode(',', array_keys($apiResults)), count($stagingImages)
        ));

        // Existing-book detection by exact ISBN match — high confidence here.
        $existingBookId = Book::where('isbn', $cleanIsbn)->value('id');

        if (empty($apiResults)) {
            $status = PendingBook::STATUS_FAILED;
            $errorMessage = "لم يتم العثور على الـISBN في أي مصدر — جرب البحث بالعنوان والمؤلف";
        } else {
            $status = $existingBookId ? PendingBook::STATUS_DUPLICATE : PendingBook::STATUS_ENRICHED;
            $errorMessage = null;
        }

        return PendingBook::create([
            'title'            => $title ?: 'ISBN: ' . $cleanIsbn,
            'author_name'      => $author ?: 'غير معروف',
            'language'         => $language,
            'status'           => $status,
            'api_results'      => $apiResults,
            'staging_images'   => $stagingImages,
            'error_message'    => $errorMessage,
            'existing_book_id' => $existingBookId,
        ]);
    }

    /**
     * Convert an enriched/failed pending row into a live Book. The admin's edits
     * are passed in $overrides — every field below is taken from the override
     * if present, otherwise falls back to the fetched value.
     */
    public function approve(PendingBook $pending, array $overrides, int $reviewerUserId): Book
    {
        if (!$pending->isReviewable()) {
            throw new \InvalidArgumentException("الطلب رقم {$pending->id} لا يمكن اعتماده في حالته الحالية ({$pending->status})");
        }

        return DB::transaction(function () use ($pending, $overrides, $reviewerUserId) {
            // The admin's per-field picks come through $overrides directly — by
            // the time we get here, every text field has the chosen value (whether
            // that came from BNF, Google Books, Open Library, or admin's typing).
            $title         = $overrides['title']               ?? $pending->title;
            $authorName    = $overrides['author_name']         ?? $pending->author_name;
            $isbn          = $overrides['isbn']                ?? null;
            $description   = $overrides['description']         ?? null;
            $pageNum       = (int) ($overrides['page_num']     ?? 0);
            $publisherName = $overrides['publisher_name']      ?? null;
            $language      = $overrides['language']            ?? $pending->language;
            $price         = (float) ($overrides['price']      ?? 0);
            $quantity      = (int) ($overrides['quantity']     ?? 0);
            $categoryIds   = array_filter((array) ($overrides['category_ids'] ?? []));

            // image_source: 'bnf' | 'google_books' | 'open_library' | 'wikipedia' | 'custom' | null
            $imageSource    = $overrides['image_source']   ?? null;
            $uploadedCover  = $overrides['uploaded_cover'] ?? null;   // \Illuminate\Http\UploadedFile|null

            // Author — if the admin picked one from autocomplete at stage time AND
            // didn't override the name to something different, bind to that exact row.
            // Otherwise firstOrCreate by name (with enrichment for newly created rows).
            $author = null;
            if ($pending->author_id) {
                $bound = Author::find($pending->author_id);
                if ($bound && mb_strtolower(trim($bound->name)) === mb_strtolower(trim($authorName))) {
                    $author = $bound;
                } else {
                    Log::info("Author binding dropped for pending #{$pending->id}: admin edited name from '" . ($bound?->name ?? 'missing') . "' to '{$authorName}'");
                }
            }
            if (!$author) {
                $author = Author::firstOrCreate(
                    ['name' => $authorName],
                    ['status' => 'active']
                );
                if ($author->wasRecentlyCreated) {
                    try {
                        app(AuthorEnrichmentService::class)->enrichAuthor($author);
                    } catch (\Throwable $e) {
                        Log::info("Author enrichment skipped for '{$author->name}': " . $e->getMessage());
                    }
                }
            }

            // Publisher — same pattern.
            $publisherId = null;
            if (!empty($publisherName)) {
                $publisher = PublishingHouse::firstOrCreate(
                    ['name' => trim($publisherName)],
                    ['status' => 'active']
                );
                $publisherId = $publisher->id;
            }

            $stagingImages  = $pending->staging_images ?? [];
            $finalImagePath = null;

            if ($imageSource === 'custom' && $uploadedCover) {
                // Manual upload — push through the existing image pipeline.
                $finalImagePath = $this->images->processLocalFile(
                    $uploadedCover->getRealPath(),
                    'images/books',
                    'book'
                );
            } elseif ($imageSource && isset($stagingImages[$imageSource])) {
                // Promote the chosen API staging image to permanent.
                $chosenStaging = $stagingImages[$imageSource];
                $stagingFull   = public_path($chosenStaging);
                if (file_exists($stagingFull)) {
                    $newName = 'book_' . time() . '_' . mt_rand(100, 999) . '.webp';
                    $finalRelative = 'images/books/' . $newName;
                    @rename($stagingFull, public_path($finalRelative));

                    $stagingThumb = str_replace('staging/', 'staging/thumbs/', $chosenStaging);
                    $stagingThumbFull = public_path($stagingThumb);
                    if (file_exists($stagingThumbFull)) {
                        @rename($stagingThumbFull, public_path('images/books/thumbs/' . $newName));
                    }
                    $finalImagePath = $finalRelative;
                }
            }

            // Discard every API staging image that wasn't the chosen one.
            foreach ($stagingImages as $src => $path) {
                if ($imageSource !== 'custom' && $src === $imageSource) {
                    continue;  // we already moved it to permanent above
                }
                @unlink(public_path($path));
                $thumb = str_replace('staging/', 'staging/thumbs/', $path);
                if (file_exists(public_path($thumb))) {
                    @unlink(public_path($thumb));
                }
            }

            // Use the first picked category as the denormalized primary category_id.
            $primaryCategoryId = $categoryIds[0] ?? null;

            // Track which source(s) were available, for reference on the new Book.
            $sourceTag = !empty($pending->api_results)
                ? implode(',', array_keys($pending->api_results))
                : null;

            $book = Book::create([
                'title'               => $title,
                'type'                => 'book',
                'product_type'        => 'standard',
                'author_id'           => $author->id,
                'description'         => $description ? strip_tags($description) : null,
                'price'               => $price,
                'category_id'         => $primaryCategoryId,
                'image'               => $finalImagePath,
                'page_num'            => $pageNum,
                'language'            => $language,
                'publishing_house_id' => $publisherId,
                'isbn'                => $isbn,
                'quantity'            => $quantity,
                'status'              => 'active',
                'api_data_status'     => 'enriched',
                'api_source'          => $sourceTag,
                'api_last_updated'    => now(),
            ]);

            // Pivot: book_authors (primary)
            BookAuthor::create([
                'book_id'     => $book->id,
                'author_id'   => $author->id,
                'author_type' => 'primary',
            ]);

            // Pivot: book_category — sync admin-picked categories, primary flag on the first
            if (!empty($categoryIds)) {
                $book->syncCategories(
                    array_map('intval', $categoryIds),
                    (int) $primaryCategoryId
                );
            }

            $pending->update([
                'status'           => PendingBook::STATUS_APPROVED,
                'approved_book_id' => $book->id,
                'reviewed_by'      => $reviewerUserId,
                'reviewed_at'      => now(),
            ]);

            return $book;
        });
    }

    public function discard(PendingBook $pending, int $reviewerUserId): void
    {
        foreach ($pending->staging_images ?? [] as $path) {
            @unlink(public_path($path));
            $thumb = str_replace('staging/', 'staging/thumbs/', $path);
            if (file_exists(public_path($thumb))) {
                @unlink(public_path($thumb));
            }
        }

        $pending->update([
            'status'         => PendingBook::STATUS_DISCARDED,
            'staging_images' => [],
            'reviewed_by'    => $reviewerUserId,
            'reviewed_at'    => now(),
        ]);
    }

    /**
     * Build all first-hop HTTP requests for stageFromTitleAuthor and fire them
     * concurrently via Http::pool. Returns a map [source => Response|ConnectionException].
     * Caller must filter out failed entries.
     */
    private function poolTitleAuthorRequests(array $sources, string $title, string $author, string $language): array
    {
        return Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($sources, $title, $author, $language) {
            $reqs = [];

            if (in_array('bnf', $sources, true)) {
                $cql = sprintf(
                    'bib.title all "%s" and bib.author all "%s"',
                    $this->cqlEscape($title),
                    $this->cqlEscape($author)
                );
                $reqs[] = $pool->as('bnf')
                    ->withOptions(['verify' => false, 'timeout' => 15])
                    ->get('https://catalogue.bnf.fr/api/SRU', [
                        'version'        => '1.2',
                        'operation'      => 'searchRetrieve',
                        'query'          => $cql,
                        'recordSchema'   => 'dublincore',
                        'maximumRecords' => 20,
                    ]);
            }

            if (in_array('google_books', $sources, true)) {
                $apiKey = app(APIService::class)->getApiKey();
                $params = [
                    'q'          => $title . ($author !== '' ? ' ' . $author : ''),
                    'key'        => $apiKey,
                    'maxResults' => 10,
                    'printType'  => 'books',
                ];
                if ($iso = APIService::isoLanguageCode($language)) {
                    $params['langRestrict'] = $iso;
                }
                $reqs[] = $pool->as('google_books')
                    ->withOptions(['verify' => false, 'timeout' => 15, 'connect_timeout' => 10])
                    ->get('https://www.googleapis.com/books/v1/volumes', $params);
            }

            if (in_array('open_library', $sources, true)) {
                $params = ['title' => $title, 'limit' => 3];
                if ($author !== '') $params['author'] = $author;
                $reqs[] = $pool->as('open_library')
                    ->withOptions(['verify' => false, 'timeout' => 15])
                    ->get('https://openlibrary.org/search.json', $params);
            }

            if (in_array('wikipedia', $sources, true)) {
                $wikiLang = self::LANGUAGE_TO_WIKI[$language] ?? null;
                if ($wikiLang) {
                    $reqs[] = $pool->as('wikipedia')
                        ->withHeaders(['User-Agent' => 'LibraryFokara/1.0 (Library Management System)'])
                        ->withOptions(['verify' => false, 'timeout' => 12])
                        ->get("https://{$wikiLang}.wikipedia.org/w/api.php", [
                            'action' => 'opensearch',
                            'search' => $title . ' ' . $author,
                            'limit'  => 5,
                            'format' => 'json',
                        ]);
                }
            }

            return $reqs;
        });
    }

    /**
     * Pool entries are either Response or ConnectionException — only the
     * successful Response objects are usable downstream.
     */
    private function isOkResponse($r): bool
    {
        return $r instanceof \Illuminate\Http\Client\Response && $r->successful();
    }

    /**
     * Normalized cache key for a single source's parsed result.
     * Lowercase+trim ensures "L'Étranger" and " l'étranger " hit the same entry.
     */
    private function ingestCacheKey(string $source, string $title, string $author, string $language): string
    {
        $normalized = mb_strtolower(trim($title)) . '|' . mb_strtolower(trim($author)) . '|' . $language;
        return "ingest:{$source}:" . md5($normalized);
    }

    /**
     * Cache key for an ISBN-keyed lookup. Language doesn't factor in — ISBN is
     * globally unique so the same book returns the same data regardless.
     */
    private function ingestIsbnCacheKey(string $source, string $cleanIsbn): string
    {
        return "ingest_isbn:{$source}:{$cleanIsbn}";
    }

    /**
     * Build pooled HTTP requests for ISBN-based lookups (no Wikipedia — its
     * ISBN search is unreliable).
     */
    private function poolIsbnRequests(array $sources, string $cleanIsbn): array
    {
        return Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($sources, $cleanIsbn) {
            $reqs = [];

            if (in_array('bnf', $sources, true)) {
                $reqs[] = $pool->as('bnf')
                    ->withOptions(['verify' => false, 'timeout' => 15])
                    ->get('https://catalogue.bnf.fr/api/SRU', [
                        'version'        => '1.2',
                        'operation'      => 'searchRetrieve',
                        'query'          => sprintf('bib.isbn any "%s"', $cleanIsbn),
                        'recordSchema'   => 'dublincore',
                        'maximumRecords' => 5,
                    ]);
            }

            if (in_array('google_books', $sources, true)) {
                $apiKey = app(APIService::class)->getApiKey();
                $reqs[] = $pool->as('google_books')
                    ->withOptions(['verify' => false, 'timeout' => 15, 'connect_timeout' => 10])
                    ->get('https://www.googleapis.com/books/v1/volumes', [
                        'q'   => 'isbn:' . $cleanIsbn,
                        'key' => $apiKey,
                    ]);
            }

            if (in_array('open_library', $sources, true)) {
                $reqs[] = $pool->as('open_library')
                    ->withOptions(['verify' => false, 'timeout' => 15])
                    ->get('https://openlibrary.org/api/books', [
                        'bibkeys' => 'ISBN:' . $cleanIsbn,
                        'format'  => 'json',
                        'jscmd'   => 'data',
                    ]);
            }

            return $reqs;
        });
    }

    /**
     * BNF ISBN lookup: query already filters to records matching this ISBN, so
     * we just take the first one. No fuzzy title scoring needed.
     */
    private function parseBnfIsbnResponse(\Illuminate\Http\Client\Response $response): ?array
    {
        if (empty($response->body())) return null;

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());
        libxml_use_internal_errors($previous);
        if ($xml === false) return null;

        $xml->registerXPathNamespace('srw', 'http://www.loc.gov/zing/srw/');
        $records = $xml->xpath('//srw:recordData');
        if (empty($records)) return null;

        $rec = $records[0];
        $rec->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        $titleResult = (string) ($rec->xpath('.//dc:title')[0] ?? '');
        if (str_contains($titleResult, ' / ')) {
            $titleResult = trim(strstr($titleResult, ' / ', true));
        }
        $author      = (string) ($rec->xpath('.//dc:creator')[0]   ?? '');
        $publisher   = (string) ($rec->xpath('.//dc:publisher')[0] ?? '');
        $description = (string) ($rec->xpath('.//dc:description')[0] ?? '');
        $subjects    = array_map('strval', $rec->xpath('.//dc:subject') ?? []);
        $isbn        = null;
        $pageNum     = 0;

        foreach ($rec->xpath('.//dc:identifier') ?? [] as $id) {
            if (preg_match('/(\d{13}|\d{10}|\d{9}X)/', (string) $id, $m)) {
                $isbn = $m[1];
                break;
            }
        }
        foreach ($rec->xpath('.//dc:format') ?? [] as $fmt) {
            if (preg_match('/(\d+)\s*p\.?/u', (string) $fmt, $m)) {
                $pageNum = (int) $m[1];
                break;
            }
        }

        $imageUrl = $isbn ? "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg" : null;

        return [
            'title'       => $titleResult ?: null,
            'author'      => $author ?: null,
            'description' => $description ?: null,
            'isbn'        => $isbn,
            'page_num'    => $pageNum,
            'publisher'   => $publisher ?: null,
            'language'    => 'french',
            'categories'  => array_slice($subjects, 0, 5),
            'image_url'   => $imageUrl,
        ];
    }

    /**
     * Open Library /api/books?jscmd=data response — completely different shape
     * from the title search endpoint. Returns a single object keyed by "ISBN:{X}".
     */
    private function parseOpenLibraryIsbnResponse(\Illuminate\Http\Client\Response $response, string $cleanIsbn): ?array
    {
        $body = $response->json();
        $key  = 'ISBN:' . $cleanIsbn;
        $doc  = $body[$key] ?? null;
        if (!$doc) return null;

        $authorName = $doc['authors'][0]['name'] ?? null;
        $publisher  = $doc['publishers'][0]['name'] ?? null;

        $imageUrl = $doc['cover']['large']
                 ?? $doc['cover']['medium']
                 ?? $doc['cover']['small']
                 ?? null;

        $subjects = [];
        foreach ((array) ($doc['subjects'] ?? []) as $s) {
            $subjects[] = is_array($s) ? ($s['name'] ?? '') : (string) $s;
        }
        $subjects = array_values(array_filter($subjects));

        return [
            'title'       => $doc['title'] ?? null,
            'author'      => $authorName,
            'description' => $doc['excerpts'][0]['text'] ?? null,
            'isbn'        => $cleanIsbn,
            'page_num'    => $doc['number_of_pages'] ?? 0,
            'publisher'   => $publisher,
            'language'    => null,
            'categories'  => array_slice($subjects, 0, 5),
            'image_url'   => $imageUrl,
        ];
    }

    /**
     * Fetch every source's cover image concurrently, then process bytes into
     * WebP + thumbnail sequentially (CPU-bound). Short per-request timeout
     * keeps a slow/dead cover endpoint (e.g. OpenLibrary 404s for BNF ISBNs)
     * from holding up the rest.
     *
     * @return array<string,string>  source => relative staging path
     */
    private function poolStageImages(array $apiResults, string $title, string $author): array
    {
        $urls = [];
        foreach ($apiResults as $source => $r) {
            if (!empty($r['image_url'])) {
                // Google Books: ask for higher resolution by upgrading zoom=1 → zoom=2
                // and dropping the page-curl artifact. Other sources untouched.
                $url = $r['image_url'];
                if ($source === 'google_books') {
                    $url = str_replace('zoom=1', 'zoom=2', $url);
                    $url = str_replace('&edge=curl', '', $url);
                }
                $urls[$source] = $url;
            }
        }

        if (empty($urls)) return [];

        // Cache pass — for each URL, if a previously-processed staging file still
        // exists on disk, COPY it to a unique new staging filename. Copying (instead
        // of sharing the same path across pending rows) keeps each pending row's
        // discard/approve independent.
        $stagingImages = [];
        $urlsToFetch   = [];
        $cacheHits     = [];
        foreach ($urls as $source => $url) {
            $cached = Cache::get($this->coverCacheKey($url));
            if ($cached && file_exists(public_path($cached))) {
                $copied = $this->copyCachedStagingFile($cached, $source, $title, $author);
                if ($copied) {
                    $stagingImages[$source] = $copied;
                    $cacheHits[] = $source;
                    continue;
                }
            }
            $urlsToFetch[$source] = $url;
        }

        if (!empty($urlsToFetch)) {
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($urlsToFetch) {
                $reqs = [];
                foreach ($urlsToFetch as $source => $url) {
                    $reqs[] = $pool->as($source)
                        ->withOptions(['verify' => false, 'timeout' => 5, 'connect_timeout' => 3])
                        ->get($url);
                }
                return $reqs;
            });

            foreach ($urlsToFetch as $source => $url) {
                try {
                    $r = $responses[$source] ?? null;
                    if (!$this->isOkResponse($r)) continue;
                    $bytes = $r->body();
                    if ($bytes === '') continue;
                    $path = $this->images->processFromBytes(
                        $bytes,
                        'images/books/staging',
                        'staging_' . $source . '_' . substr(md5($title . $author), 0, 8)
                    );
                    if ($path) {
                        $stagingImages[$source] = $path;
                        Cache::put($this->coverCacheKey($url), $path, now()->addHours(24));
                    }
                } catch (\Throwable $e) {
                    Log::info("BookIngestion {$source} cover process failed for '{$title}': " . $e->getMessage());
                }
            }
        }

        Log::debug(sprintf(
            "BookIngestion cover cache — hits: %s | fetched: %s",
            $cacheHits ? implode(',', $cacheHits) : 'none',
            $urlsToFetch ? implode(',', array_keys($urlsToFetch)) : 'none'
        ));

        return $stagingImages;
    }

    private function coverCacheKey(string $url): string
    {
        return "ingest_cover:" . md5($url);
    }

    /**
     * Copy a previously-cached staging file (and its thumbnail) to a new unique
     * staging filename. Each pending row gets its own physical file so one row's
     * discard/approve can't invalidate another row's staging image.
     * Returns the new relative path, or null on failure.
     */
    private function copyCachedStagingFile(string $cachedRelative, string $source, string $title, string $author): ?string
    {
        $newFilename = 'staging_' . $source . '_' . substr(md5($title . $author . uniqid('', true)), 0, 12) . '.webp';
        $newRelative = 'images/books/staging/' . $newFilename;

        if (!@copy(public_path($cachedRelative), public_path($newRelative))) {
            return null;
        }

        $cachedThumb = str_replace('staging/', 'staging/thumbs/', $cachedRelative);
        $newThumb    = 'images/books/staging/thumbs/' . $newFilename;
        if (file_exists(public_path($cachedThumb))) {
            $thumbDir = public_path('images/books/staging/thumbs');
            if (!file_exists($thumbDir)) mkdir($thumbDir, 0755, true);
            @copy(public_path($cachedThumb), public_path($newThumb));
        }

        return $newRelative;
    }

    /**
     * Parse a Google Books volumes response into the normalized shape.
     */
    private function parseGoogleBooksResponse(\Illuminate\Http\Client\Response $response): ?array
    {
        $raw  = $response->json();
        $info = $raw['items'][0]['volumeInfo'] ?? null;
        if (!$info || empty($info['title'])) {
            return null;
        }

        $isbn = null;
        foreach ($info['industryIdentifiers'] ?? [] as $id) {
            if (($id['type'] ?? '') === 'ISBN_13') {
                $isbn = $id['identifier'];
                break;
            }
            if (($id['type'] ?? '') === 'ISBN_10' && !$isbn) {
                $isbn = $id['identifier'];
            }
        }

        return [
            'title'       => $info['title'],
            'author'      => isset($info['authors'][0]) ? $info['authors'][0] : null,
            'description' => $info['description']   ?? null,
            'isbn'        => $isbn,
            'page_num'    => $info['pageCount']     ?? 0,
            'publisher'   => $info['publisher']     ?? null,
            'language'    => self::LANGUAGE_MAP[$info['language'] ?? ''] ?? null,
            'categories'  => $info['categories']    ?? [],
            'image_url'   => $info['imageLinks']['thumbnail']
                          ?? $info['imageLinks']['smallThumbnail']
                          ?? null,
        ];
    }

    /**
     * Parse a BNF SRU XML response into the normalized shape. BNF's recordset
     * is searched for the best title match — see pickBestBnfRecord().
     */
    private function parseBnfResponse(\Illuminate\Http\Client\Response $response, string $title): ?array
    {
        if (empty($response->body())) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());
        libxml_use_internal_errors($previous);

        // Strict false-check: SimpleXMLElement is "falsy" when its root has no
        // direct unprefixed children, which is the case for BNF (everything is
        // under srw:). Don't use `!$xml` here.
        if ($xml === false) {
            return null;
        }

        $xml->registerXPathNamespace('srw', 'http://www.loc.gov/zing/srw/');
        $records = $xml->xpath('//srw:recordData');
        if (empty($records)) {
            return null;
        }

        $best = $this->pickBestBnfRecord($records, $title);
        if (!$best) {
            return null;
        }

        $titleResult = (string) ($best['record']->xpath('.//dc:title')[0] ?? '');
        if (str_contains($titleResult, ' / ')) {
            $titleResult = trim(strstr($titleResult, ' / ', true));
        }
        $authorName  = (string) ($best['record']->xpath('.//dc:creator')[0] ?? '');
        $publisher   = (string) ($best['record']->xpath('.//dc:publisher')[0]   ?? '');
        $description = (string) ($best['record']->xpath('.//dc:description')[0] ?? '');
        $subjects    = array_map('strval', $best['record']->xpath('.//dc:subject') ?? []);
        $isbn        = $best['isbn'];
        $pageNum     = 0;

        foreach ($best['record']->xpath('.//dc:format') ?? [] as $fmt) {
            if (preg_match('/(\d+)\s*p\.?/u', (string) $fmt, $m)) {
                $pageNum = (int) $m[1];
                break;
            }
        }

        if ($titleResult === '' && empty($subjects) && $isbn === null) {
            return null;
        }

        $imageUrl = $isbn
            ? "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg"
            : null;

        return [
            'title'       => $titleResult ?: $title,
            'author'      => $authorName ?: null,
            'description' => $description ?: null,
            'isbn'        => $isbn,
            'page_num'    => $pageNum,
            'publisher'   => $publisher ?: null,
            'language'    => 'french',
            'categories'  => array_slice($subjects, 0, 5),
            'image_url'   => $imageUrl,
        ];
    }

    /**
     * BNF's CQL syntax requires escaping quotes inside literal strings.
     */
    private function cqlEscape(string $value): string
    {
        return str_replace('"', '\\"', $value);
    }

    /**
     * Score each BNF record by title similarity × ISBN presence and return the
     * winner. Audio recordings and unrelated subseries score low and drop out.
     *
     * @param array<int, \SimpleXMLElement> $records
     * @return array{record: \SimpleXMLElement, isbn: ?string}|null
     */
    private function pickBestBnfRecord(array $records, string $wantedTitle): ?array
    {
        $wantedNormalized = mb_strtolower(trim($wantedTitle));
        $bestScore  = 0.0;
        $bestRecord = null;
        $bestIsbn   = null;

        // Hard floor: any record whose title is < 50% similar to the input is
        // rejected outright. Stops compilations and unrelated works from winning
        // just because they have an ISBN. Below this floor we'd rather let
        // Google Books / Open Library handle it.
        $minSimilarity = 50.0;

        foreach ($records as $rec) {
            $rec->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $rawTitle = (string) ($rec->xpath('.//dc:title')[0] ?? '');
            if ($rawTitle === '') {
                continue;
            }

            // Strip attribution suffix before comparing.
            $cleanTitle = str_contains($rawTitle, ' / ')
                ? trim(strstr($rawTitle, ' / ', true))
                : $rawTitle;

            similar_text(
                $wantedNormalized,
                mb_strtolower($cleanTitle),
                $similarity
            );

            if ($similarity < $minSimilarity) {
                continue;
            }

            // Among similar-enough titles, prefer the ones with ISBNs (real books
            // over audio/braille editions/manuscripts). Score = similarity × isbn-multiplier.
            $isbn = null;
            foreach ($rec->xpath('.//dc:identifier') ?? [] as $id) {
                if (preg_match('/(\d{13}|\d{10}|\d{9}X)/', (string) $id, $m)) {
                    $isbn = $m[1];
                    break;
                }
            }

            $score = $similarity * ($isbn ? 1.2 : 1.0);

            if ($score > $bestScore) {
                $bestScore  = $score;
                $bestRecord = $rec;
                $bestIsbn   = $isbn;
            }
        }

        if ($bestRecord === null) {
            return null;
        }

        return [
            'record' => $bestRecord,
            'isbn'   => $bestIsbn,
        ];
    }

    private function parseOpenLibraryResponse(\Illuminate\Http\Client\Response $response, string $title): ?array
    {
        $doc = $response->json('docs.0');
        if (!$doc) {
            return null;
        }

        $imageUrl = !empty($doc['cover_i'])
            ? "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-L.jpg"
            : null;

        return [
            'title'       => $doc['title'] ?? $title,
            'author'      => $doc['author_name'][0] ?? null,
            'description' => $doc['first_sentence'][0] ?? null,
            'isbn'        => $doc['isbn'][0] ?? null,
            'page_num'    => $doc['number_of_pages_median'] ?? 0,
            'publisher'   => $doc['publisher'][0] ?? null,
            'language'    => self::LANGUAGE_MAP[$doc['language'][0] ?? ''] ?? null,
            'categories'  => array_slice($doc['subject'] ?? [], 0, 5),
            'image_url'   => $imageUrl,
        ];
    }

    /**
     * Pick the best Wikipedia opensearch candidate and fetch its summary.
     * Sequential second hop after the parallel pool.
     */
    private function finishWikipediaLookup(\Illuminate\Http\Client\Response $searchResponse, string $title, string $language): ?array
    {
        $wikiLang = self::LANGUAGE_TO_WIKI[$language] ?? null;
        if (!$wikiLang) {
            return null;
        }

        $payload = $searchResponse->json();
        $pageTitles = $payload[1] ?? [];
        if (empty($pageTitles)) {
            return null;
        }

        $best = null;
        $bestScore = 0;
        foreach ($pageTitles as $pt) {
            similar_text(mb_strtolower($title), mb_strtolower($pt), $sim);
            if ($sim > $bestScore) {
                $bestScore = $sim;
                $best = $pt;
            }
        }

        if ($bestScore < 50 || !$best) {
            return null;
        }

        $summaryUrl = "https://{$wikiLang}.wikipedia.org/api/rest_v1/page/summary/" . rawurlencode($best);
        $summaryRes = Http::withHeaders(['User-Agent' => 'LibraryFokara/1.0 (Library Management System)'])
            ->withOptions(['verify' => false, 'timeout' => 12])
            ->get($summaryUrl);

        if (!$summaryRes->successful()) {
            return null;
        }

        $s = $summaryRes->json();
        if (empty($s) || empty($s['title'])) {
            return null;
        }

        return [
            'title'       => $s['title'],
            'description' => $s['extract']     ?? null,
            'isbn'        => null,
            'page_num'    => 0,
            'publisher'   => null,
            'language'    => $language,
            'categories'  => [],
            'image_url'   => $s['thumbnail']['source'] ?? ($s['originalimage']['source'] ?? null),
        ];
    }

    /**
     * Same internal-language → Wikipedia subdomain map used by lookupWikipedia.
     */
    private const LANGUAGE_TO_WIKI = [
        'arabic'  => 'ar',
        'english' => 'en',
        'french'  => 'fr',
        'spanish' => 'es',
        'german'  => 'de',
    ];

    /**
     * Find a live Book with the same author (exact) and a fuzzy-similar title.
     * Mirrors the pattern in BookCoverImportService::findDuplicate but scoped to
     * the author's books, which is much smaller and avoids scanning the full table.
     */
    private function findFuzzyDuplicate(string $title, string $author): ?int
    {
        $authorId = Author::where('name', $author)->value('id');
        if (!$authorId) {
            return null;
        }

        $needle = mb_strtolower(trim($title));
        $candidates = Book::query()
            ->where(function ($q) use ($authorId) {
                $q->where('author_id', $authorId)
                  ->orWhereHas('authors', fn($qq) => $qq->where('authors.id', $authorId));
            })
            ->whereNull('deleted_at')
            ->select('id', 'title')
            ->get();

        foreach ($candidates as $c) {
            similar_text($needle, mb_strtolower($c->title), $percent);
            if ($percent >= 85) {
                return $c->id;
            }
        }

        return null;
    }
}
