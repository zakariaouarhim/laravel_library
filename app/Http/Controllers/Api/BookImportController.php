<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookImportController extends Controller
{
    /**
     * Fail-closed shared-secret check for every /api/import endpoint.
     * Callers (n8n, the admin review page) must send X-Import-Token.
     */
    private function authorizeImport(Request $request): void
    {
        $token = (string) config('services.import.token');

        if ($token === '' || !hash_equals($token, (string) $request->header('X-Import-Token'))) {
            abort(401, 'Unauthorized');
        }
    }

    /**
     * Only allow file paths that the staging step itself produced
     * (exact match against staged-books.json), with an image extension.
     * Blocks arbitrary-file reads like file_path=/var/www/.../.env.
     */
    private function assertStagedPath(string $path): void
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            abort(422, 'Invalid file type');
        }

        $stagedFile = storage_path('app/staged-books.json');
        $staged = file_exists($stagedFile) ? json_decode(file_get_contents($stagedFile), true) : null;

        if (!is_array($staged)) {
            abort(422, 'No staged data');
        }

        foreach ($staged as $entry) {
            if (($entry['file_path'] ?? null) === $path) {
                return;
            }
        }

        abort(422, 'file_path is not part of the staged import');
    }

    /**
     * GET /api/import/staged
     * Return the staged books JSON for n8n to consume.
     */
    public function staged(Request $request): JsonResponse
    {
        $this->authorizeImport($request);

        $path = storage_path('app/staged-books.json');

        if (!file_exists($path)) {
            return response()->json(['error' => 'No staged data found. Run: php artisan books:import-from-covers --stage'], 404);
        }

        $data = json_decode(file_get_contents($path), true);

        return response()->json([
            'total' => count($data),
            'books' => $data,
        ]);
    }

    /**
     * POST /api/import/book
     * Create a single book from n8n-approved data.
     *
     * Expected JSON body:
     * {
     *   "title": "...",
     *   "author": "...",
     *   "description": "...",
     *   "isbn": "...",
     *   "publisher": "...",
     *   "page_num": 0,
     *   "language": "arabic",
     *   "price": 80.00,
     *   "category_name": "كتب عربية",
     *   "file_path": "C:/Users/.../image.jpg",
     *   "source": "google_books"
     * }
     */
    public function store(Request $request, ImageService $imageService): JsonResponse
    {
        $this->authorizeImport($request);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'author' => 'nullable|string|max:300',
            'description' => 'nullable|string',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:300',
            'page_num' => 'nullable|integer|min:0',
            'language' => 'required|string|in:arabic,english,french,spanish,german',
            'price' => 'required|numeric|min:0',
            'category_name' => 'required|string',
            'file_path' => 'required|string',
            'source' => 'nullable|string',
        ]);

        $this->assertStagedPath($validated['file_path']);

        // Check duplicate
        $existing = Book::where('title', $validated['title'])->first();
        if ($existing) {
            return response()->json([
                'status' => 'skipped',
                'message' => 'Book already exists',
                'book_id' => $existing->id,
            ], 409);
        }

        try {
            $book = Book::withoutEvents(function () use ($validated, $imageService) {
                return DB::transaction(function () use ($validated, $imageService) {

                    // Category
                    $category = Category::firstOrCreate(['name' => $validated['category_name']]);

                    // Author
                    $authorId = null;
                    if (!empty($validated['author'])) {
                        $author = Author::firstOrCreate(
                            ['name' => trim($validated['author'])],
                            ['status' => 'active']
                        );
                        $authorId = $author->id;
                    }

                    // Publisher
                    $publisherId = null;
                    if (!empty($validated['publisher'])) {
                        $publisher = PublishingHouse::firstOrCreate(
                            ['name' => trim($validated['publisher'])],
                            ['status' => 'active']
                        );
                        $publisherId = $publisher->id;
                    }

                    // Process image
                    $imagePath = null;
                    if (!empty($validated['file_path']) && file_exists($validated['file_path'])) {
                        $prefix = 'import_' . substr(md5(basename($validated['file_path'])), 0, 8);
                        $imagePath = $imageService->processLocalFile(
                            $validated['file_path'],
                            'images/books',
                            $prefix
                        );
                    }

                    // Create book
                    $book = Book::create([
                        'title' => $validated['title'],
                        'type' => 'book',
                        'author_id' => $authorId,
                        'description' => $validated['description'] ? strip_tags($validated['description']) : null,
                        'price' => $validated['price'],
                        'discount' => 0,
                        'category_id' => $category->id,
                        'image' => $imagePath,
                        'page_num' => $validated['page_num'] ?? 0,
                        'language' => $validated['language'],
                        'publishing_house_id' => $publisherId,
                        'isbn' => $validated['isbn'],
                        'quantity' => 10,
                        'api_data_status' => ($validated['source'] && $validated['source'] !== 'none') ? 'enriched' : 'pending',
                        'api_source' => $validated['source'],
                        'status' => 'active',
                    ]);

                    // Pivots
                    if ($authorId) {
                        $book->authors()->attach($authorId, ['author_type' => 'primary']);
                    }
                    $book->categories()->attach($category->id, ['is_primary' => true]);

                    return $book;
                });
            });

            return response()->json([
                'status' => 'created',
                'book_id' => $book->id,
                'title' => $book->title,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/import/book/image
     * Serve a local book cover image as base64 for n8n preview.
     */
    public function serveImage(Request $request): JsonResponse
    {
        $this->authorizeImport($request);

        $path = (string) $request->input('file_path');

        if ($path === '') {
            return response()->json(['error' => 'File not found'], 404);
        }

        $this->assertStagedPath($path);

        if (!file_exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        return response()->json([
            'base64' => base64_encode(file_get_contents($path)),
            'mime_type' => $mimeMap[$ext] ?? 'image/jpeg',
            'filename' => basename($path),
        ]);
    }
}
