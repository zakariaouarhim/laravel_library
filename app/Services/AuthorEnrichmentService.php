<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Author;

class AuthorEnrichmentService
{
    protected $openLibraryUrl = 'https://openlibrary.org';
    protected $wikiArUrl = 'https://ar.wikipedia.org/w/api.php';
    protected $wikidataUrl = 'https://www.wikidata.org/w/api.php';
    protected $userAgent = 'LibraryFokara/1.0 (Library Management System; contact@library-fokara.com)';

    /**
     * Detect if string contains Arabic characters
     */
    protected function isArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }

    /**
     * Main enrichment: routes to Wikipedia Arabic or Open Library based on name script
     */
    public function enrichAuthor(Author $author): ?array
    {
        if ($this->isArabic($author->name)) {
            // Arabic name → Wikipedia Arabic API
            $result = $this->enrichFromWikipediaArabic($author);
            if ($result) {
                return $result;
            }
            // Fallback to Open Library anyway
            return $this->enrichFromOpenLibrary($author);
        }

        // Latin name → Open Library first, then Wikipedia English
        $result = $this->enrichFromOpenLibrary($author);
        if ($result) {
            return $result;
        }
        return $this->enrichFromWikipediaArabic($author);
    }

    // =================== Wikipedia Arabic API ===================

    /**
     * Enrich author from Arabic Wikipedia + Wikidata
     */
    protected function enrichFromWikipediaArabic(Author $author): ?array
    {
        // Step 1: Search for the author on Arabic Wikipedia
        Log::info("Wikipedia Arabic: searching for '{$author->name}'");
        $pageTitle = $this->searchWikipediaArabic($author->name);
        if (!$pageTitle) {
            Log::warning("Wikipedia Arabic: no search results for '{$author->name}'");
            return null;
        }
        Log::info("Wikipedia Arabic: found page '{$pageTitle}'");

        // Step 2: Get page content (extract text for biography)
        $pageData = $this->getWikipediaArabicPage($pageTitle);
        if (!$pageData) {
            Log::warning("Wikipedia Arabic: could not fetch page data for '{$pageTitle}'");
            return null;
        }
        Log::info("Wikipedia Arabic: got page data, extract length=" . mb_strlen($pageData['extract'] ?? ''));

        // Step 3: Get structured data from Wikidata (dates, nationality, website, image)
        $wikidataId = $pageData['wikidata_id'] ?? null;
        $wikidataFields = [];
        if ($wikidataId) {
            $wikidataFields = $this->getWikidataStructuredData($wikidataId);
            Log::info("Wikidata: got structured data for {$wikidataId}", $wikidataFields);
        }

        return $this->mapWikipediaData($pageData, $pageTitle, $wikidataFields, $author);
    }

    /**
     * Search Arabic Wikipedia for an author
     */
    protected function searchWikipediaArabic(string $name): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->withOptions([
                'verify' => false,
                'timeout' => 15,
                'connect_timeout' => 10,
            ])->get($this->wikiArUrl, [
                'action' => 'query',
                'list' => 'search',
                'srsearch' => $name,
                'srnamespace' => 0,
                'srlimit' => 5,
                'format' => 'json',
                'utf8' => 1,
            ]);

            if (!$response->successful()) {
                Log::error("Wikipedia Arabic search HTTP error: status={$response->status()}");
                return null;
            }

            $data = $response->json();
            Log::info("Wikipedia Arabic search raw response keys: " . implode(',', array_keys($data ?? [])));

            $results = $data['query']['search'] ?? [];

            if (empty($results)) {
                Log::warning("Wikipedia Arabic search: empty results for '{$name}'");
                return null;
            }

            Log::info("Wikipedia Arabic search: found " . count($results) . " results, first: " . ($results[0]['title'] ?? 'N/A'));

            // Find best match by title similarity
            $normalizedName = $this->normalizeName($name);
            foreach ($results as $result) {
                $resultNormalized = $this->normalizeName($result['title']);
                similar_text($normalizedName, $resultNormalized, $percent);
                if ($percent >= 70) {
                    return $result['title'];
                }
            }

            // Fallback: return first result (Wikipedia search is usually accurate)
            return $results[0]['title'];
        } catch (\Exception $e) {
            Log::error("Wikipedia Arabic search error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get Arabic Wikipedia page data (extract text + categories + properties)
     */
    protected function getWikipediaArabicPage(string $title): ?array
    {
        try {
            // Get page extract (summary text)
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->withOptions([
                'verify' => false,
                'timeout' => 15,
            ])->get($this->wikiArUrl, [
                'action' => 'query',
                'titles' => $title,
                'prop' => 'extracts|pageprops|categories',
                'exintro' => true,
                'explaintext' => true,
                'ppprop' => 'wikibase_item',
                'cllimit' => 20,
                'format' => 'json',
                'utf8' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            $page = reset($pages);

            if (!$page || isset($page['missing'])) {
                return null;
            }

            return [
                'title' => $page['title'] ?? $title,
                'extract' => $page['extract'] ?? null,
                'wikidata_id' => $page['pageprops']['wikibase_item'] ?? null,
                'categories' => array_map(function ($cat) {
                    return str_replace('تصنيف:', '', $cat['title'] ?? '');
                }, $page['categories'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error("Wikipedia Arabic page fetch error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get structured data from Wikidata (birth/death dates, nationality, website, image)
     * Uses properties: P569 (birth), P570 (death), P27 (citizenship), P856 (website), P18 (image)
     */
    protected function getWikidataStructuredData(string $wikidataId): array
    {
        $result = [
            'birth_date' => null,
            'death_date' => null,
            'nationality' => null,
            'website' => null,
            'photo_url' => null,
        ];

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->withOptions([
                'verify' => false,
                'timeout' => 15,
            ])->get($this->wikidataUrl, [
                'action' => 'wbgetentities',
                'ids' => $wikidataId,
                'props' => 'claims',
                'format' => 'json',
            ]);

            if (!$response->successful()) {
                Log::error("Wikidata API error: status={$response->status()}");
                return $result;
            }

            $entity = $response->json()['entities'][$wikidataId] ?? [];
            $claims = $entity['claims'] ?? [];

            // P569 = date of birth
            $dob = $claims['P569'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
            if ($dob) {
                $result['birth_date'] = $this->parseWikidataDate($dob);
            }

            // P570 = date of death
            $dod = $claims['P570'][0]['mainsnak']['datavalue']['value']['time'] ?? null;
            if ($dod) {
                $result['death_date'] = $this->parseWikidataDate($dod);
            }

            // P27 = country of citizenship → get Arabic label
            $citizenshipId = $claims['P27'][0]['mainsnak']['datavalue']['value']['id'] ?? null;
            if ($citizenshipId) {
                $result['nationality'] = $this->getWikidataLabel($citizenshipId, 'ar');
            }

            // P856 = official website
            $result['website'] = $claims['P856'][0]['mainsnak']['datavalue']['value'] ?? null;

            // P18 = image
            $imageFilename = $claims['P18'][0]['mainsnak']['datavalue']['value'] ?? null;
            if ($imageFilename) {
                $imageFilename = str_replace(' ', '_', $imageFilename);
                $md5 = md5($imageFilename);
                $result['photo_url'] = "https://upload.wikimedia.org/wikipedia/commons/{$md5[0]}/{$md5[0]}{$md5[1]}/{$imageFilename}";
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Wikidata structured data error: {$e->getMessage()}");
            return $result;
        }
    }

    /**
     * Parse Wikidata time format (+1911-12-11T00:00:00Z) to Y-m-d
     */
    protected function parseWikidataDate(string $time): ?string
    {
        // Format: +1911-12-11T00:00:00Z
        if (preg_match('/([+-]?\d{4})-(\d{2})-(\d{2})/', $time, $m)) {
            $year = ltrim($m[1], '+');
            return sprintf('%04d-%02d-%02d', $year, $m[2], $m[3]);
        }
        return null;
    }

    /**
     * Get Arabic label for a Wikidata entity (e.g. country name)
     */
    protected function getWikidataLabel(string $entityId, string $lang = 'ar'): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->withOptions([
                'verify' => false,
                'timeout' => 10,
            ])->get($this->wikidataUrl, [
                'action' => 'wbgetentities',
                'ids' => $entityId,
                'props' => 'labels',
                'languages' => $lang,
                'format' => 'json',
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $response->json()['entities'][$entityId]['labels'][$lang]['value'] ?? null;
        } catch (\Exception $e) {
            Log::error("Wikidata label fetch error: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Map Wikipedia + Wikidata data to our author fields
     */
    protected function mapWikipediaData(array $pageData, string $pageTitle, array $wikidataFields, Author $author): array
    {
        $mapped = [
            'api_source' => 'wikipedia_ar',
            'api_id' => $pageData['wikidata_id'] ?? $pageTitle,
            'api_name' => $pageData['title'],
            'search_match_name' => $pageData['title'],
        ];

        // Biography from Wikipedia extract (Arabic text)
        $bio = $pageData['extract'] ?? null;
        if ($bio) {
            $bio = trim($bio);
            if (mb_strlen($bio) > 50) {
                $mapped['biography'] = $bio;
            }
        }

        // Birth date from Wikidata P569
        if (!empty($wikidataFields['birth_date'])) {
            $mapped['birth_date'] = $wikidataFields['birth_date'];
            $mapped['birth_date_raw'] = $wikidataFields['birth_date'];
        }

        // Death date from Wikidata P570
        if (!empty($wikidataFields['death_date'])) {
            $mapped['death_date'] = $wikidataFields['death_date'];
            $mapped['death_date_raw'] = $wikidataFields['death_date'];
        }

        // Nationality from Wikidata P27 (Arabic label)
        if (!empty($wikidataFields['nationality'])) {
            $mapped['nationality'] = $wikidataFields['nationality'];
        }

        // Website from Wikidata P856
        if (!empty($wikidataFields['website'])) {
            $mapped['website'] = $wikidataFields['website'];
        }

        // Image from Wikidata P18
        if (!empty($wikidataFields['photo_url'])) {
            $mapped['photo_url'] = $wikidataFields['photo_url'];
        }

        // Wikipedia link
        $mapped['wikipedia_url'] = "https://ar.wikipedia.org/wiki/" . urlencode(str_replace(' ', '_', $pageTitle));

        // Categories as subjects
        $categories = $pageData['categories'] ?? [];
        if (!empty($categories)) {
            $mapped['top_subjects'] = array_slice($categories, 0, 5);
        }

        return $mapped;
    }

    // =================== Open Library (for Latin names) ===================

    /**
     * Enrich from Open Library
     */
    protected function enrichFromOpenLibrary(Author $author): ?array
    {
        $searchResults = $this->searchOpenLibrary($author->name);
        if (!$searchResults) {
            return null;
        }

        $bestMatch = $this->findBestMatch($author->name, $searchResults);
        if (!$bestMatch) {
            return null;
        }

        $olid = $bestMatch['key'];
        $details = $this->getOpenLibraryDetails($olid);

        if (!$details) {
            return $this->mapOpenLibrarySearch($bestMatch, $author);
        }

        return $this->mapOpenLibraryDetails($details, $bestMatch, $author);
    }

    protected function searchOpenLibrary(string $name): ?array
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 15,
                'connect_timeout' => 10,
            ])->get("{$this->openLibraryUrl}/search/authors.json", [
                'q' => $name,
                'limit' => 5,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            return !empty($data['docs']) ? $data['docs'] : null;
        } catch (\Exception $e) {
            Log::error("Open Library search error: {$e->getMessage()}");
            return null;
        }
    }

    protected function getOpenLibraryDetails(string $olid): ?array
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 15,
            ])->get("{$this->openLibraryUrl}/authors/{$olid}.json");

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("Open Library details error: {$e->getMessage()}");
            return null;
        }
    }

    protected function mapOpenLibraryDetails(array $details, array $searchResult, Author $author): array
    {
        $mapped = [
            'api_source' => 'open_library',
            'api_id' => $searchResult['key'] ?? null,
            'api_name' => $details['name'] ?? ($searchResult['name'] ?? null),
            'search_match_name' => $searchResult['name'] ?? null,
        ];

        $bio = $details['bio'] ?? null;
        if (is_array($bio)) {
            $bio = $bio['value'] ?? null;
        }
        if ($bio && (!$author->biography || strlen($bio) > strlen($author->biography))) {
            $mapped['biography'] = $bio;
        }

        $birthDate = $details['birth_date'] ?? ($searchResult['birth_date'] ?? null);
        if ($birthDate && !$author->birth_date) {
            $mapped['birth_date'] = $this->parseDate($birthDate);
            $mapped['birth_date_raw'] = $birthDate;
        }

        $deathDate = $details['death_date'] ?? ($searchResult['death_date'] ?? null);
        if ($deathDate && !$author->death_date) {
            $mapped['death_date'] = $this->parseDate($deathDate);
            $mapped['death_date_raw'] = $deathDate;
        }

        $olid = $searchResult['key'] ?? null;
        if ($olid && !$author->profile_image) {
            $mapped['photo_url'] = "https://covers.openlibrary.org/a/olid/{$olid}-L.jpg";
        }

        if (isset($details['links']) && is_array($details['links'])) {
            foreach ($details['links'] as $link) {
                if (!$author->website && isset($link['url'])) {
                    $mapped['website'] = $link['url'];
                    break;
                }
            }
        }

        if (isset($searchResult['work_count'])) {
            $mapped['work_count'] = $searchResult['work_count'];
        }

        if (isset($searchResult['top_subjects'])) {
            $mapped['top_subjects'] = array_slice($searchResult['top_subjects'], 0, 5);
        }

        return $mapped;
    }

    protected function mapOpenLibrarySearch(array $result, Author $author): array
    {
        $mapped = [
            'api_source' => 'open_library',
            'api_id' => $result['key'] ?? null,
            'api_name' => $result['name'] ?? null,
            'search_match_name' => $result['name'] ?? null,
        ];

        if (isset($result['birth_date']) && !$author->birth_date) {
            $mapped['birth_date'] = $this->parseDate($result['birth_date']);
            $mapped['birth_date_raw'] = $result['birth_date'];
        }
        if (isset($result['death_date']) && !$author->death_date) {
            $mapped['death_date'] = $this->parseDate($result['death_date']);
            $mapped['death_date_raw'] = $result['death_date'];
        }
        $olid = $result['key'] ?? null;
        if ($olid && !$author->profile_image) {
            $mapped['photo_url'] = "https://covers.openlibrary.org/a/olid/{$olid}-L.jpg";
        }
        if (isset($result['work_count'])) {
            $mapped['work_count'] = $result['work_count'];
        }
        if (isset($result['top_subjects'])) {
            $mapped['top_subjects'] = array_slice($result['top_subjects'], 0, 5);
        }
        return $mapped;
    }

    // =================== Shared Utilities ===================

    protected function findBestMatch(string $name, array $results): ?array
    {
        $normalizedName = $this->normalizeName($name);

        foreach ($results as $result) {
            $resultName = $this->normalizeName($result['name'] ?? '');
            if ($resultName === $normalizedName) {
                return $result;
            }
            similar_text($normalizedName, $resultName, $percent);
            if ($percent >= 80) {
                return $result;
            }
        }

        if (!empty($results[0])) {
            $resultName = $this->normalizeName($results[0]['name'] ?? '');
            similar_text($normalizedName, $resultName, $percent);
            if ($percent >= 50) {
                return $results[0];
            }
        }

        return null;
    }

    public function normalizeName(string $name): string
    {
        // Remove Arabic diacritics
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $name);
        // Normalize whitespace
        $name = preg_replace('/\s+/', ' ', trim($name));
        return mb_strtolower($name);
    }

    protected function parseDate(?string $dateStr): ?string
    {
        if (!$dateStr) {
            return null;
        }

        foreach (['Y-m-d', 'Y', 'd M Y', 'M d, Y', 'F d, Y', 'Y-m'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, trim($dateStr));
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (preg_match('/(\d{4})/', $dateStr, $matches)) {
            return $matches[1] . '-01-01';
        }

        return null;
    }

    /**
     * Download and store author photo as WebP
     */
    public function downloadPhoto(string $url, Author $author): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
            ])->withOptions([
                'verify' => false,
                'timeout' => 15,
            ])->get($url);

            if (!$response->successful()) {
                return null;
            }

            $content = $response->body();

            // Skip tiny images (placeholders)
            if (strlen($content) < 1000) {
                Log::info("Author photo too small (likely placeholder), skipping for: {$author->name}");
                return null;
            }

            $image = \Intervention\Image\Laravel\Facades\Image::read($content);
            $image->scale(width: 300);
            $encoded = $image->toWebp(80);

            $filename = 'authors/' . uniqid('author_') . '.webp';
            Storage::disk('public')->put($filename, (string) $encoded);

            return $filename;
        } catch (\Exception $e) {
            Log::error("Failed to download author photo: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Check for duplicate/similar authors in the database
     */
    public function findSimilarAuthors(string $name, ?int $excludeId = null): array
    {
        $normalizedName = $this->normalizeName($name);
        $authors = Author::when($excludeId, function ($q) use ($excludeId) {
            $q->where('id', '!=', $excludeId);
        })->get();

        $similar = [];
        foreach ($authors as $author) {
            $authorNormalized = $this->normalizeName($author->name);
            similar_text($normalizedName, $authorNormalized, $percent);

            if ($percent >= 75) {
                $similar[] = [
                    'id' => $author->id,
                    'name' => $author->name,
                    'similarity' => round($percent, 1),
                    'books_count' => $author->primaryBooks()->count(),
                ];
            }
        }

        usort($similar, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        return $similar;
    }
}
