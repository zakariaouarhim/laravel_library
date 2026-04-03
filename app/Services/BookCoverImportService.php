<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookCoverImportService
{
    protected string $progressFile;

    public function __construct(
        private ClaudeVisionService $claudeVision,
        private APIService $apiService,
        private ImageService $imageService,
    ) {
        $this->progressFile = storage_path('app/book-import-progress.json');
    }

    /**
     * Process a single image file through the full import pipeline.
     *
     * @return array{status: string, book_id: ?int, title: ?string, author: ?string, source: ?string, errors: array}
     */
    public function processImage(
        string $filePath,
        string $folderKey,
        Category $category,
        array $folderConfig,
        bool $dryRun = false
    ): array {
        $result = [
            'status' => 'failed',
            'book_id' => null,
            'title' => null,
            'author' => null,
            'source' => null,
            'errors' => [],
        ];

        try {
            // Step 1: Extract title/author from cover via Claude Vision
            $visionData = $this->claudeVision->extractBookInfo($filePath, $folderConfig['language_hint']);

            $result['title'] = $visionData['title'];
            $result['author'] = $visionData['author'];

            if (empty($visionData['title'])) {
                $result['status'] = 'failed';
                $result['errors'][] = 'Claude Vision could not extract title from cover';
                return $result;
            }

            // Step 2: Duplicate check
            $existing = $this->findDuplicate($visionData['title']);
            if ($existing) {
                $result['status'] = 'skipped_duplicate';
                $result['book_id'] = $existing->id;
                $result['errors'][] = "Duplicate of book #{$existing->id}: {$existing->title}";
                return $result;
            }

            // Step 3: Enrich metadata (3-tier fallback)
            $enrichment = $this->enrichMetadata(
                $visionData['title'],
                $visionData['author'],
                $folderConfig['language']
            );
            $result['source'] = $enrichment['source'];

            if ($dryRun) {
                $result['status'] = 'dry_run';
                $result['enrichment'] = $enrichment;
                return $result;
            }

            // Step 4: Process image to WebP
            $imagePath = $this->imageService->processLocalFile(
                $filePath,
                'images/books',
                'import_' . substr(md5(basename($filePath)), 0, 8)
            );

            // Step 5: Create book record
            $book = $this->createBook($visionData, $enrichment, $imagePath, $category, $folderConfig);
            $result['status'] = 'success';
            $result['book_id'] = $book->id;
            $result['title'] = $book->title;

        } catch (\Exception $e) {
            $result['status'] = 'failed';
            $result['errors'][] = $e->getMessage();
            Log::error("Import failed for {$filePath}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Enrich book metadata using 3-tier fallback strategy.
     * Tier 1: Google Books API (existing APIService)
     * Tier 2: Open Library API
     * Tier 3: Claude-generated description
     */
    protected function enrichMetadata(string $title, ?string $author, string $language): array
    {
        $data = [
            'description' => null,
            'page_num' => 0,
            'publisher' => null,
            'isbn' => null,
            'api_language' => null,
            'source' => null,
        ];

        // Tier 1: Google Books
        try {
            $googleData = $this->apiService->fetchBookDataByTitle($title, $author);
            if (isset($googleData['items'][0]['volumeInfo'])) {
                $info = $googleData['items'][0]['volumeInfo'];
                $data['description'] = $info['description'] ?? null;
                $data['page_num'] = $info['pageCount'] ?? 0;
                $data['publisher'] = $info['publisher'] ?? null;
                $data['api_language'] = $info['language'] ?? null;

                // Extract ISBN
                foreach ($info['industryIdentifiers'] ?? [] as $id) {
                    if ($id['type'] === 'ISBN_13') {
                        $data['isbn'] = $id['identifier'];
                        break;
                    }
                    if ($id['type'] === 'ISBN_10' && !$data['isbn']) {
                        $data['isbn'] = $id['identifier'];
                    }
                }

                $data['source'] = 'google_books';
                return $data;
            }
        } catch (\Exception $e) {
            Log::info("Google Books lookup failed for '{$title}': " . $e->getMessage());
        }

        // Tier 2: Open Library
        try {
            $olData = $this->searchOpenLibrary($title, $author);
            if ($olData) {
                $data['description'] = $olData['description'] ?? null;
                $data['page_num'] = $olData['page_num'] ?? 0;
                $data['publisher'] = $olData['publisher'] ?? null;
                $data['isbn'] = $olData['isbn'] ?? null;
                $data['api_language'] = $olData['language'] ?? null;
                $data['source'] = 'open_library';
                return $data;
            }
        } catch (\Exception $e) {
            Log::info("Open Library lookup failed for '{$title}': " . $e->getMessage());
        }

        // Tier 3: Claude-generated description
        try {
            $desc = $this->claudeVision->generateDescription($title, $author, $language);
            if ($desc) {
                $data['description'] = $desc;
                $data['source'] = 'claude_generated';
                return $data;
            }
        } catch (\Exception $e) {
            Log::info("Claude description generation failed for '{$title}': " . $e->getMessage());
        }

        $data['source'] = 'none';
        return $data;
    }

    /**
     * Tier 2: Open Library search.
     */
    protected function searchOpenLibrary(string $title, ?string $author): ?array
    {
        $params = ['title' => $title, 'limit' => 3];
        if ($author) {
            $params['author'] = $author;
        }

        $response = Http::withOptions([
            'verify' => false,
            'timeout' => 15,
        ])->get('https://openlibrary.org/search.json', $params);

        if (!$response->successful()) {
            return null;
        }

        $docs = $response->json('docs', []);
        if (empty($docs)) {
            return null;
        }

        $doc = $docs[0];
        return [
            'description' => $doc['first_sentence'][0] ?? null,
            'page_num' => $doc['number_of_pages_median'] ?? 0,
            'publisher' => $doc['publisher'][0] ?? null,
            'isbn' => $doc['isbn'][0] ?? null,
            'language' => $doc['language'][0] ?? null,
        ];
    }

    /**
     * Check for duplicate books by exact title or fuzzy match.
     */
    protected function findDuplicate(string $title): ?Book
    {
        $title = trim($title);

        // Exact match
        $exact = Book::where('title', $title)->first();
        if ($exact) {
            return $exact;
        }

        // Fuzzy match on recent books (avoid scanning entire table)
        $candidates = Book::select('id', 'title')
            ->where('type', 'book')
            ->latest()
            ->limit(2000)
            ->get();

        foreach ($candidates as $candidate) {
            similar_text(
                mb_strtolower($title),
                mb_strtolower($candidate->title),
                $percent
            );
            if ($percent >= 85) {
                return Book::find($candidate->id);
            }
        }

        return null;
    }

    /**
     * Create the Book record with all gathered data.
     * Wrapped in withoutEvents to prevent BookObserver spam.
     */
    protected function createBook(
        array $visionData,
        array $enrichmentData,
        ?string $imagePath,
        Category $category,
        array $folderConfig
    ): Book {
        return Book::withoutEvents(function () use ($visionData, $enrichmentData, $imagePath, $category, $folderConfig) {
            return DB::transaction(function () use ($visionData, $enrichmentData, $imagePath, $category, $folderConfig) {

                // Create/find author
                $authorId = null;
                $authorName = $visionData['author'] ?? null;
                if ($authorName) {
                    $author = Author::firstOrCreate(
                        ['name' => trim($authorName)],
                        ['status' => 'active']
                    );
                    $authorId = $author->id;
                }

                // Create/find publisher
                $publisherName = $enrichmentData['publisher'] ?? null;
                $publisherId = null;
                if ($publisherName) {
                    $publisher = PublishingHouse::firstOrCreate(
                        ['name' => trim($publisherName)],
                        ['status' => 'active']
                    );
                    $publisherId = $publisher->id;
                }

                // Determine API data status
                $apiStatus = $enrichmentData['source'] && $enrichmentData['source'] !== 'none'
                    ? 'enriched'
                    : 'pending';

                // Strip HTML from description
                $description = $enrichmentData['description']
                    ? strip_tags($enrichmentData['description'])
                    : null;

                // Create book
                $book = Book::create([
                    'title' => $visionData['title'],
                    'type' => 'book',
                    'author_id' => $authorId,
                    'description' => $description,
                    'price' => $folderConfig['price'],
                    'discount' => 0,
                    'category_id' => $category->id,
                    'image' => $imagePath,
                    'page_num' => $enrichmentData['page_num'] ?? 0,
                    'language' => $folderConfig['language'],
                    'publishing_house_id' => $publisherId,
                    'isbn' => $enrichmentData['isbn'] ?? null,
                    'quantity' => 10,
                    'api_data_status' => $apiStatus,
                    'api_source' => $enrichmentData['source'],
                    'status' => 'active',
                ]);

                // Attach to book_authors pivot
                if ($authorId) {
                    $book->authors()->attach($authorId, ['author_type' => 'primary']);
                }

                // Attach to book_category pivot
                $book->categories()->attach($category->id, ['is_primary' => true]);

                return $book;
            });
        });
    }

    /**
     * Stage mode: extract + enrich only, return data for n8n review.
     * No DB writes, no image processing.
     */
    public function stageImage(string $filePath, string $folderKey, array $folderConfig): array
    {
        $result = [
            'status' => 'failed',
            'book_id' => null,
            'title' => null,
            'author' => null,
            'source' => null,
            'errors' => [],
            // Staging-specific fields
            'file_path' => $filePath,
            'folder_key' => $folderKey,
            'description' => null,
            'publisher' => null,
            'isbn' => null,
            'page_num' => 0,
            'language' => $folderConfig['language'],
            'price' => $folderConfig['price'],
            'category_name' => $folderConfig['category_name'] ?? null,
            'confidence' => 0,
        ];

        try {
            // Step 1: Claude Vision extraction
            $visionData = $this->claudeVision->extractBookInfo($filePath, $folderConfig['language_hint']);

            $result['title'] = $visionData['title'];
            $result['author'] = $visionData['author'];
            $result['confidence'] = $visionData['confidence'] ?? 0;

            if (empty($visionData['title'])) {
                $result['errors'][] = 'Could not extract title from cover';
                return $result;
            }

            // Step 2: Enrich metadata
            $enrichment = $this->enrichMetadata(
                $visionData['title'],
                $visionData['author'],
                $folderConfig['language']
            );

            $result['source'] = $enrichment['source'];
            $result['description'] = $enrichment['description'] ? strip_tags($enrichment['description']) : null;
            $result['publisher'] = $enrichment['publisher'];
            $result['isbn'] = $enrichment['isbn'];
            $result['page_num'] = $enrichment['page_num'] ?? 0;
            $result['status'] = 'staged';

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("Stage failed for {$filePath}: " . $e->getMessage());
        }

        return $result;
    }

    // --- Progress file management ---

    public function loadProgress(): array
    {
        if (file_exists($this->progressFile)) {
            $data = json_decode(file_get_contents($this->progressFile), true);
            return is_array($data) ? $data : ['files' => []];
        }
        return ['files' => [], 'started_at' => now()->toIso8601String()];
    }

    public function saveProgress(array $progress): void
    {
        $progress['last_updated'] = now()->toIso8601String();
        file_put_contents($this->progressFile, json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function clearProgress(): void
    {
        if (file_exists($this->progressFile)) {
            unlink($this->progressFile);
        }
    }

    public function getProgressFilePath(): string
    {
        return $this->progressFile;
    }
}
