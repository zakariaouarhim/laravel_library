<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Read-only lookup of a book across the web sources: BNF (French only),
 * Google Books, Open Library and Wikipedia (per language). Queries them
 * concurrently, caches each source's answer for 24h, and returns the
 * per-source normalized result.
 *
 * It writes nothing. The staging queue this class used to own (pending_books,
 * plus its own Book-creation path) was retired once searching the sources
 * became a step inside catalogue-import, which persists a result as an `api:`
 * catalogue row and reviews it like any other.
 *
 * Callers: EnrichPreviewService (the per-field source picker in both import
 * screens), the catalogue-import "find this book" panel, and books:ingest.
 */
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

    /**
     * Look a book up by title (+ optional author) across every source that
     * suits the language — BNF only for French, Wikipedia per language.
     * Concurrent, 24h cached per source, and a cached "nothing found" counts.
     *
     * @return array<string,array> source => normalized book data
     */
    public function resolveSources(string $title, string $author, string $language = 'french', bool $force = false): array
    {
        $title  = trim($title);
        $author = trim($author);

        $sources = $language === 'french'
            ? ['bnf', 'google_books', 'open_library', 'wikipedia']
            : ['google_books', 'open_library', 'wikipedia'];

        $apiResults     = [];
        $sourcesToFetch = [];

        foreach ($sources as $source) {
            if ($force) { $sourcesToFetch[] = $source; continue; }
            $cached = Cache::get($this->ingestCacheKey($source, $title, $author, $language));
            if ($cached === null) {
                $sourcesToFetch[] = $source;
            } else {
                $value = $cached['v'] ?? null;
                if (is_array($value)) $apiResults[$source] = $value;
            }
        }

        $responses = !empty($sourcesToFetch)
            ? $this->poolTitleAuthorRequests($sourcesToFetch, $title, $author, $language)
            : [];

        if (in_array('google_books', $sourcesToFetch, true)) {
            $responses['google_books'] = $this->retryGoogleBooks(
                $responses['google_books'] ?? null,
                $this->googleBooksTitleParams($title, $author, $language)
            );
        }

        foreach (['bnf', 'google_books', 'open_library'] as $source) {
            if (!in_array($source, $sourcesToFetch, true)) continue;
            try {
                $response = $responses[$source] ?? null;
                if (!$this->isOkResponse($response)) {
                    // Transport failure (Google 503 etc.), not "book unknown":
                    // don't cache it for 24h, let the next request try again.
                    continue;
                }
                $parsed = match ($source) {
                    'bnf'          => $this->parseBnfResponse($response, $title),
                    'google_books' => $this->parseGoogleBooksResponse($response),
                    'open_library' => $this->parseOpenLibraryResponse($response, $title),
                };
                Cache::put($this->ingestCacheKey($source, $title, $author, $language), ['v' => $parsed], now()->addHours(24));
                if ($parsed) $apiResults[$source] = $parsed;
            } catch (\Throwable $e) {
                Log::info("BookIngestion {$source} parse failed for '{$title}': " . $e->getMessage());
            }
        }

        if (in_array('wikipedia', $sourcesToFetch, true)) {
            try {
                $searchResp = $responses['wikipedia'] ?? null;
                if ($this->isOkResponse($searchResp)) {
                    $parsed = $this->finishWikipediaLookup($searchResp, $title, $language);
                    Cache::put($this->ingestCacheKey('wikipedia', $title, $author, $language), ['v' => $parsed], now()->addHours(24));
                    if ($parsed) $apiResults['wikipedia'] = $parsed;
                }
            } catch (\Throwable $e) {
                Log::info("BookIngestion wikipedia lookup failed for '{$title}': " . $e->getMessage());
            }
        }

        return $apiResults;
    }

    /**
     * Look a book up by ISBN. No Wikipedia — its ISBN search is unreliable.
     * Concurrent and 24h cached, like resolveSources().
     *
     * @return array<string,array> source => normalized book data
     */
    public function resolveSourcesByIsbn(string $isbn, string $language = 'french', bool $force = false): array
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (strlen($cleanIsbn) < 10) {
            return [];
        }

        $sources = $language === 'french'
            ? ['bnf', 'google_books', 'open_library']
            : ['google_books', 'open_library'];

        $apiResults     = [];
        $sourcesToFetch = [];

        foreach ($sources as $source) {
            if ($force) { $sourcesToFetch[] = $source; continue; }
            $cached = Cache::get($this->ingestIsbnCacheKey($source, $cleanIsbn));
            if ($cached === null) {
                $sourcesToFetch[] = $source;
            } else {
                $value = $cached['v'] ?? null;
                if (is_array($value)) $apiResults[$source] = $value;
            }
        }

        $responses = !empty($sourcesToFetch)
            ? $this->poolIsbnRequests($sourcesToFetch, $cleanIsbn)
            : [];

        if (in_array('google_books', $sourcesToFetch, true)) {
            $responses['google_books'] = $this->retryGoogleBooks(
                $responses['google_books'] ?? null,
                $this->googleBooksIsbnParams($cleanIsbn)
            );
        }

        foreach ($sourcesToFetch as $source) {
            try {
                $response = $responses[$source] ?? null;
                if (!$this->isOkResponse($response)) {
                    // Transport failure (Google 503 etc.), not "book unknown":
                    // don't cache it for 24h, let the next request try again.
                    continue;
                }
                $parsed = match ($source) {
                    'bnf'          => $this->parseBnfIsbnResponse($response),
                    'google_books' => $this->parseGoogleBooksResponse($response),
                    'open_library' => $this->parseOpenLibraryIsbnResponse($response, $cleanIsbn),
                };
                Cache::put($this->ingestIsbnCacheKey($source, $cleanIsbn), ['v' => $parsed], now()->addHours(24));
                if ($parsed) $apiResults[$source] = $parsed;
            } catch (\Throwable $e) {
                Log::info("BookIngestion ISBN {$source} parse failed for '{$cleanIsbn}': " . $e->getMessage());
            }
        }

        return $apiResults;
    }

    /**
     * Build all first-hop HTTP requests for a title+author lookup and fire them
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
                $reqs[] = $pool->as('google_books')
                    ->withOptions(['verify' => false, 'timeout' => 15, 'connect_timeout' => 10])
                    ->get('https://www.googleapis.com/books/v1/volumes', $this->googleBooksTitleParams($title, $author, $language));
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

    /** Query params for a Google Books title+author search (pool + retry). */
    private function googleBooksTitleParams(string $title, string $author, string $language): array
    {
        $params = [
            'q'          => $title . ($author !== '' ? ' ' . $author : ''),
            'key'        => app(APIService::class)->getApiKey(),
            'maxResults' => 10,
            'printType'  => 'books',
        ];
        if ($iso = APIService::isoLanguageCode($language)) {
            $params['langRestrict'] = $iso;
        }

        return $params;
    }

    /** Query params for a Google Books ISBN lookup (pool + retry). */
    private function googleBooksIsbnParams(string $cleanIsbn): array
    {
        return [
            'q'   => 'isbn:' . $cleanIsbn,
            'key' => app(APIService::class)->getApiKey(),
        ];
    }

    /**
     * Google's Books API intermittently 503s (~half of all requests as of
     * mid-2026), and pooled requests can't retry in this Laravel version — so
     * when the pooled attempt failed, re-send it synchronously with retries.
     * Returns the original response/exception if every attempt fails.
     */
    private function retryGoogleBooks($response, array $params)
    {
        if ($this->isOkResponse($response)) {
            return $response;
        }

        try {
            return Http::withOptions(['verify' => false, 'timeout' => 15, 'connect_timeout' => 10])
                ->retry(3, 1000, fn($e) => true)
                ->get('https://www.googleapis.com/books/v1/volumes', $params);
        } catch (\Throwable $e) {
            Log::info('BookIngestion google_books retry exhausted: ' . $e->getMessage());

            return $response;
        }
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
                $reqs[] = $pool->as('google_books')
                    ->withOptions(['verify' => false, 'timeout' => 15, 'connect_timeout' => 10])
                    ->get('https://www.googleapis.com/books/v1/volumes', $this->googleBooksIsbnParams($cleanIsbn));
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

}
