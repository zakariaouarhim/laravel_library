<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreBookProductRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use App\Models\Follow;
use App\Models\Series;
use App\Models\UserNotification;
use App\Services\BookAdminService;
use App\Services\BookEnrichmentService;
use App\Services\BookSearchService;
use Illuminate\Support\Facades\DB;

class AdminBookController extends Controller
{
    public function __construct(
        private BookAdminService $adminService,
        private BookEnrichmentService $enrichmentService,
        private BookSearchService $searchService,
    ) {}

    public function getProductsApi(Request $request)
    {
        // Get search and status filters from request
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        // Start query
        $query = Book::with(['category', 'primaryAuthor', 'publishingHouse']);

        // Apply search filter (title, author, ISBN)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhereHas('primaryAuthor', fn($q) => $q->where('name', 'like', "%$search%"))
                  ->orWhere('isbn', 'like', "%$search%");
            });
        }

        // Apply status filter (api_data_status)
        if (!empty($status)) {
            $query->where('api_data_status', $status);
        }

        // Paginate results (10 per page)
        $products = $query->paginate(10);

        // Get stats for the cards
        $stats = [
            'total' => Book::count(),
            'enriched' => Book::where('api_data_status', 'enriched')->count(),
            'pending' => Book::where('api_data_status', 'pending')->orWhereNull('api_data_status')->count(),
        ];

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * Get API stats for dashboard cards
     */
    public function getProductsApiStats()
    {
        $stats = [
            'total' => Book::count(),
            'enriched' => Book::where('api_data_status', 'enriched')->count(),
            'pending' => Book::where('api_data_status', 'pending')->orWhereNull('api_data_status')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    public function getProductById($id)
    {
        try {
            $product = Book::with(['category', 'primaryAuthor', 'publishingHouse', 'series'])->find($id);

            if ($product) {
                return response()->json([
                    'success' => true,
                    'data' => $product
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Get Product Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }

    /**
     * AI-rewrite a product description for SEO (same service as the
     * catalogue/reader import review screens). No persistence — returns the
     * text; the modal sends description_rewritten=1 on save so the nightly
     * books:rewrite-descriptions cron skips the book.
     */
    public function rewriteDescription(Request $request, \App\Services\DescriptionRewriteService $rewriter)
    {
        $data = $request->validate([
            'name'        => 'nullable|string|max:500',
            'author'      => 'nullable|string|max:300',
            'description' => 'required|string',
            'language'    => 'nullable|string|max:30',
        ]);

        // Unknown/free-text language → null: the service then asks the model
        // to keep the language of the input text.
        $language = strtolower(trim($data['language'] ?? ''));
        if (!in_array($language, ['arabic', 'english', 'french', 'spanish', 'german'], true)) {
            $language = null;
        }

        $result = $rewriter->rewrite(
            trim($data['name'] ?? '') ?: '—',
            $data['author'] ?? null,
            trim($data['description']),
            $language
        );

        if (!$result['ok']) {
            return response()->json(['success' => false, 'message' => 'فشلت إعادة الصياغة: ' . $result['error']], 502);
        }

        return response()->json(['success' => true, 'description' => $result['text']]);
    }

    /**
     * Enrich preview from all sources (catalogue/BNF/Google Books/Open
     * Library/Wikipedia) for the product modals — same service as the
     * import review screens, but not bound to any import row.
     */
    public function enrichPreview(Request $request, \App\Services\EnrichPreviewService $enricher)
    {
        $data = $request->validate([
            'name'     => 'nullable|string|max:500',
            'author'   => 'nullable|string|max:300',
            'isbn'     => 'nullable|string|max:30',
            'language' => 'nullable|string|max:30',
        ]);

        $language = strtolower(trim($data['language'] ?? ''));
        if (!in_array($language, ['arabic', 'english', 'french', 'spanish', 'german'], true)) {
            $language = 'arabic';
        }

        $result = $enricher->preview(
            (string) ($data['name'] ?? ''),
            (string) ($data['author'] ?? ''),
            $data['isbn'] ?? null,
            $language,
            []
        );

        return response()->json($result['body'], $result['status']);
    }

    public function addProduct(StoreBookProductRequest $request)
    {
        $validated = $request->validated();

        $imagePath = null;
        $zoomW = (float) $request->input('image_zoom_w', 1);
        $zoomH = (float) $request->input('image_zoom_h', 1);

        if ($request->hasFile('productImage')) {
            try {
                $imagePath = $this->adminService->processBookImage($request->file('productImage'), null, $zoomW, $zoomH);
            } catch (\Exception $e) {
                \Log::error('Image upload failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Image upload failed'], 500);
            }
        } elseif ($request->filled('image_url')) {
            // Cover picked in the enrich panel: download it server-side.
            $imagePath = $this->adminService->processBookImageFromUrl($request->input('image_url'), null, $zoomW, $zoomH);
        }

        try {
            // Honor the bound author/publisher IDs from the autocomplete picker.
            // When the admin picked from the dropdown, we bind to that exact row
            // instead of firstOrCreate-ing on the name string (which would create
            // duplicates like "Albert Camus" vs "albert camus").
            $author = !empty($validated['productauthor_id'])
                ? \App\Models\Author::find($validated['productauthor_id'])
                : $this->adminService->findOrCreateAuthor($validated['productauthor']);
            if (!$author) {
                $author = $this->adminService->findOrCreateAuthor($validated['productauthor']);
            }

            $publishingHouseId = !empty($validated['ProductPublishingHouse_id'])
                ? (int) $validated['ProductPublishingHouse_id']
                : $this->adminService->findOrCreatePublishingHouse($validated['ProductPublishingHouse'] ?? null);

            $product = new Book();
            $product->title = $validated['productName'];
            $product->author_id = $author->id;
            $product->price = $validated['productPrice'];
            $product->category_id = $validated['primary_category_id'];
            $product->description = $validated['productDescription'];
            $product->image = $imagePath;
            $product->page_num = $validated['productNumPages'] ?? null;
            $product->language = $validated['productLanguage'] ?? null;
            $product->publishing_house_id = $publishingHouseId;
            $product->isbn = $validated['productIsbn'] ?? null;
            $product->quantity = $validated['productQuantity'];
            $product->series_id = $validated['series_id'] ?? null;
            $product->volume_number = $validated['volume_number'] ?? null;
            $product->meta_title = $validated['meta_title'] ?? null;
            $product->meta_description = $validated['meta_description'] ?? null;
            $product->api_data_status = 'pending';

            // Admin used the AI rewrite button: keep the pre-rewrite text and
            // mark it so the nightly rewrite cron skips this book.
            if ($request->boolean('description_rewritten')) {
                $product->original_description = $request->input('original_description') ?: null;
                $product->rewrite_status = 'rewritten';
                $product->rewritten_at = now();
            }

            // Handle Algolia/Scout crash gracefully
            try {
                $product->save();
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Algolia') || str_contains($e->getMessage(), 'scout')) {
                    \Log::warning('Product saved to DB, but Algolia sync failed: ' . $e->getMessage());
                } else {
                    throw $e;
                }
            }

            $product->authors()->syncWithoutDetaching([
                $author->id => ['author_type' => 'primary']
            ]);
            $product->syncCategories($validated['categories'], $validated['primary_category_id']);

            // Notify followers
            $notifyUserIds = collect();
            $notifyUserIds = $notifyUserIds->merge(Follow::followersOf('author', $author->id));
            if ($publishingHouseId) {
                $notifyUserIds = $notifyUserIds->merge(Follow::followersOf('publisher', $publishingHouseId));
            }
            foreach ($notifyUserIds->unique() as $userId) {
                UserNotification::newBook($userId, $product);
            }

            // Enrichment (optional, non-blocking)
            $message = 'Product added successfully!';
            if ($request->boolean('auto_enrich')) {
                try {
                    $this->bookService->enrichBookFromAPI($product);
                    $message = 'Product added and enriched successfully!';
                } catch (\Exception $e) {
                    \Log::warning('Enrichment failed: ' . $e->getMessage());
                }
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message, 'product' => $product]);
            }
            return redirect()->route('admin.Dashbord_Admin.product')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('CRITICAL Error adding product: ' . $e->getMessage());

            if (!isset($product->id) && $imagePath && file_exists(public_path($imagePath))) {
                unlink(public_path($imagePath));
            }

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'فشل في إضافة المنتج، يرجى المحاولة لاحقاً.'], 500);
            }
            return redirect()->back()->withErrors(['error' => 'فشل في إضافة المنتج، يرجى المحاولة لاحقاً.'])->withInput();
        }
    }

    public function enrichBook(Book $book)
    {
        $result = $this->enrichmentService->enrichBook($book);
        $status = $result['status'] ?? ($result['success'] ? 200 : 500);
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function previewEnrichment(Book $book)
    {
        $result = $this->enrichmentService->previewEnrichment($book);
        $status = $result['status'] ?? ($result['success'] ? 200 : 500);
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function applySelectedEnrichment(Request $request, Book $book)
    {
        $result = $this->enrichmentService->applySelectedFields($book, $request->input('selected_fields', []));
        $status = $result['status'] ?? ($result['success'] ? 200 : 500);
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function getPendingEnrichment()
    {
        $books = Book::needsEnrichment()->with('category')->get();
        return response()->json($books);
    }

    public function bulkEnrichBooks(Request $request)
    {
        $bookIds = $request->input('book_ids', $request->input('product_ids', []));

        if (empty($bookIds)) {
            return response()->json(['success' => false, 'message' => 'No books selected'], 400);
        }

        $enriched = 0;
        $failed = 0;
        $errors = [];

        foreach ($bookIds as $bookId) {
            try {
                $book = Book::findOrFail($bookId);
                $this->enrichmentService->enrichBookFromAPI($book);
                $enriched++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Book ID {$bookId}: " . $e->getMessage();
                \Log::error('Failed to enrich book ID ' . $bookId . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'enriched' => $enriched,
            'enriched_count' => $enriched,
            'failed' => $failed,
            'errors' => $errors,
            'message' => "Enrichment completed. Enriched: {$enriched}, Failed: {$failed}"
        ]);
    }

    // Additional method to handle book creation with author assignment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'author_id' => 'nullable|exists:authors,id',
            'author_name' => 'nullable|string|max:191',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'publishing_house_id' => 'nullable|exists:publishing_houses,id',
            'isbn' => 'required|string|unique:books,isbn',
            'page_num' => 'required|integer|min:1',
            'language' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $bookData = $validated;

        // Handle author creation if author_name is provided but author_id is not
        if (!$request->author_id && $request->author_name) {
            $author = \App\Models\Author::firstOrCreate(
                ['name' => $request->author_name],
                ['status' => 'active']
            );
            $bookData['author_id'] = $author->id;
        } elseif ($request->author_id) {
            $author = \App\Models\Author::find($request->author_id);
        }

        $book = Book::create($bookData);

        // Sync category to pivot table
        if ($book->category_id) {
            $book->syncCategories([$book->category_id], $book->category_id);
        }

        // Create primary author relationship in book_authors table
        if (isset($author)) {
            \App\Models\BookAuthor::create([
                'book_id' => $book->id,
                'author_id' => $author->id,
                'author_type' => 'primary'
            ]);
        }

        // Notify followers of the author and publisher
        $notifyUserIds = collect();

        if (isset($author)) {
            $notifyUserIds = $notifyUserIds->merge(
                Follow::followersOf('author', $author->id)
            );
        }

        if ($request->publishing_house_id) {
            $notifyUserIds = $notifyUserIds->merge(
                Follow::followersOf('publisher', $request->publishing_house_id)
            );
        }

        foreach ($notifyUserIds->unique() as $userId) {
            UserNotification::newBook($userId, $book);
        }

        return redirect()->route('books.index')->with('success', 'Book created successfully!');
    }

    public function showproduct(Request $request)
    {
        $search = $request->search;
        $categoryId = $request->category;

        $query = Book::with(['category', 'primaryAuthor', 'publishingHouse']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhereHas('primaryAuthor', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($categoryId) {
            $category = Category::with('parent', 'children')->find($categoryId);

            if ($category) {
                $categoryIds = collect([
                    $category->id,
                    optional($category->parent)->id,
                    ...$category->children->pluck('id')
                ])->filter()->unique();

                $query->whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $categoryIds));
            }
        }

        $products = $query->latest()
                        ->paginate(15)
                        ->withQueryString();

        //get categories with children for dropdown/checkboxes
        $categories = Category::whereNull('parent_id')->with('children')->get();
        // Get statistics for stats cards
        $totalProducts = Book::count();
        $availableProducts = Book::where('quantity', '>', 0)->count();
        $totalCategories = DB::table('book_category')->distinct('category_id')->count('category_id');

        $allSeries = Series::orderBy('name')->get(['id', 'name']);

        return view('Dashbord_Admin.product', compact(
            'products',
            'totalProducts',
            'availableProducts',
            'totalCategories',
            'categories',
            'allSeries'
        ));
    }

    public function viewProduct($id)
    {
        $product = Book::with(['categories', 'primaryAuthor', 'publishingHouse', 'series'])->findOrFail($id);

        return response()->json($product);
    }

    public function updateProduct(UpdateBookRequest $request, $id)
    {
        try {
            $product = Book::findOrFail($id);

            $validated = $request->validated();

            // Honor optional bound IDs from the autocomplete picker; fall back to
            // firstOrCreate on the name string when no binding was sent (admin
            // typed manually or kept the existing untouched value).
            $author = !empty($validated['author_id'])
                ? \App\Models\Author::find($validated['author_id'])
                : null;
            if (!$author) {
                $author = $this->adminService->findOrCreateAuthor($validated['author']);
            }

            $publishingHouseId = !empty($validated['publishing_house_id'])
                ? (int) $validated['publishing_house_id']
                : $this->adminService->findOrCreatePublishingHouse($validated['publishing_house'] ?? null);
            $wasOutOfStock = ($product->quantity == 0);

            // Update fields
            $product->title = $validated['title'];
            $product->description = $validated['description'];
            $product->price = $validated['price'];
            $product->author_id = $author->id;
            $product->page_num = $validated['page_num'] ?? null;
            $product->language = $validated['language'] ?? null;
            $product->publishing_house_id = $publishingHouseId;
            $product->isbn = $validated['isbn'] ?? null;
            $product->quantity = $validated['quantity'];
            $product->series_id = $validated['series_id'] ?? null;
            $product->volume_number = $validated['volume_number'] ?? null;
            // SEO overrides — `null` (empty form) clears the column and re-enables
            // MetaBuilder's auto-generated fallback.
            $product->meta_title = $validated['meta_title'] ?? null;
            $product->meta_description = $validated['meta_description'] ?? null;

            // Admin used the AI rewrite button: keep the pre-rewrite text and
            // mark it so the nightly rewrite cron skips this book.
            if ($request->boolean('description_rewritten')) {
                $product->original_description = $request->input('original_description') ?: null;
                $product->rewrite_status = 'rewritten';
                $product->rewritten_at = now();
            }

            // Handle categories
            if (!empty($validated['categories'])) {
                $product->category_id = $validated['primary_category_id'] ?? $validated['categories'][0];
            } elseif (isset($validated['category_id'])) {
                $product->category_id = $validated['category_id'];
            }

            // Handle image upload. zoom_w/zoom_h = admin's crop sliders; they
            // also apply to the EXISTING cover when no new file is chosen.
            $zoomW = (float) $request->input('image_zoom_w', 1);
            $zoomH = (float) $request->input('image_zoom_h', 1);
            if ($request->hasFile('image')) {
                try {
                    $product->image = $this->adminService->processBookImage($request->file('image'), $product->image, $zoomW, $zoomH);
                } catch (\Exception $imageError) {
                    \Log::error('Image upload error: ' . $imageError->getMessage());
                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'Failed to upload image: ' . $imageError->getMessage()], 500);
                    }
                }
            } elseif ($request->filled('image_url')) {
                // Cover picked in the enrich panel: download it server-side.
                $newPath = $this->adminService->processBookImageFromUrl($request->input('image_url'), $product->image, $zoomW, $zoomH);
                if ($newPath) {
                    $product->image = $newPath;
                }
            } elseif (($zoomW > 1.01 || $zoomH > 1.01) && $product->image && file_exists(public_path($product->image))) {
                // Re-crop the current cover in place (no new upload).
                try {
                    $product->image = $this->adminService->processBookImage(public_path($product->image), $product->image, $zoomW, $zoomH);
                } catch (\Exception $imageError) {
                    \Log::error('Image re-crop error: ' . $imageError->getMessage());
                }
            }

            $saved = $product->saveQuietly();
            if (!$saved) {
                throw new \Exception('Failed to save product to database');
            }

            $product->authors()->sync([$author->id => ['author_type' => 'primary']]);

            if (!empty($validated['categories'])) {
                $primaryId = $validated['primary_category_id'] ?? $validated['categories'][0];
                $product->syncCategories($validated['categories'], $primaryId);
            }

            // Notify stock subscribers if restocked
            if ($wasOutOfStock && $product->quantity > 0) {
                $this->adminService->notifyStockSubscribers($product);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'تم تحديث المنتج بنجاح!', 'product' => $product->load('category')]);
            }
            return redirect()->back()->with('success', 'Product updated successfully!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود.'], 404);
            }
            return redirect()->back()->withErrors(['error' => 'Product not found.']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'فشل في التحقق من البيانات', 'errors' => $e->errors()], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            \Log::error('Update product error: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحديث المنتج، يرجى المحاولة لاحقاً.'], 500);
            }
            return redirect()->back()->withErrors(['error' => 'An error occurred while updating the product.'])->withInput();
        }
    }

    /**
     * Soft-delete a product (book). The DashboardProduct.js delete button issues
     * DELETE /admin/products/api/{id} and expects { success: bool }. Book uses
     * SoftDeletes, so the row is retained (recoverable) and Scout drops it from
     * the search index automatically.
     */
    public function destroyProduct(Request $request, $id)
    {
        try {
            $product = Book::findOrFail($id);
            $product->delete();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف المنتج بنجاح']);
            }
            return redirect()->route('products.index')->with('success', 'تم حذف المنتج بنجاح');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المنتج غير موجود.'], 404);
            }
            return redirect()->back()->withErrors(['error' => 'Product not found.']);

        } catch (\Exception $e) {
            \Log::error('Delete product error: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء حذف المنتج، يرجى المحاولة لاحقاً.'], 500);
            }
            return redirect()->back()->withErrors(['error' => 'An error occurred while deleting the product.']);
        }
    }

    public function searchBook(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3|max:100']);

        return response()->json(
            $this->searchService->searchForAdmin($request->query('q'))
        );
    }

}
