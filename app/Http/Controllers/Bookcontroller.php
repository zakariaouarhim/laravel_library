<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookEnrichmentService;
use App\Services\BookSearchService;
use App\Services\BookAdminService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Follow;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function __construct(
        private BookEnrichmentService $enrichmentService,
        private BookSearchService $searchService,
        private BookAdminService $adminService,
    ) {}
   
    public function show($id)
    {
        // Get the book with its category, category's parent, and author relationship
        $book = Book::with(['category.parent', 'categories', 'primaryAuthor', 'publishingHouse'])->findOrFail($id);

        // Get all authors (cached 1 hour)
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());

        // Get all active publishing houses (cached 1 hour)
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());

        // Get the primary author of this book (if using relationship)
        $primaryAuthor = $book->primaryAuthor;
        
        // Get other books by the same author
        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::where('author_id', $primaryAuthor->id)
                ->where('id', '!=', $book->id)
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->take(10)
                ->get();
        } else {
            // If no author relationship, try to find books by author name
            $authorBooks = Book::whereHas('primaryAuthor', fn($q) => $q->where('id', $book->author_id))
                ->where('id', '!=', $book->id)
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->take(10)
                ->get();
        }

        // Get related books from the same categories with author relationship
        $bookCategoryIds = $book->categories->pluck('id')->toArray();
        $relatedBooks = Book::with('primaryAuthor')
            ->whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $bookCategoryIds))
            ->where('id', '!=', $book->id)
            ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
            ->take(10)
            ->get();

        // If no related books found in same categories, try parent category
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::with('primaryAuthor')
                ->whereHas('categories', fn($q) => $q->where('book_category.category_id', $book->category->parent_id))
                ->where('id', '!=', $book->id)
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->take(10)
                ->get();
        }

        // If still no related books, get random books from same author
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = $authorBooks->take(10);
        }

        // If still empty, get latest books (last resort)
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::with('primaryAuthor')
                ->where('id', '!=', $book->id)
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->latest()
                ->take(10)
                ->get();
        }

        // Get other books from the same publisher
        $publisherBooks = collect();
        if ($book->publishing_house_id) {
            $publisherBooks = Book::with('primaryAuthor')
                ->where('publishing_house_id', $book->publishing_house_id)
                ->where('id', '!=', $book->id)
                ->where('type', 'book')
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->take(10)
                ->get();
        }

        // Track recently viewed books in session
        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_diff($recentlyViewed, [$id]);
        array_unshift($recentlyViewed, (int) $id);
        $recentlyViewed = array_slice($recentlyViewed, 0, 10);
        session()->put('recently_viewed', $recentlyViewed);

        return view('moredetail', compact(
            'book',
            'relatedBooks',
            'authors',
            'publishingHouses',
            'primaryAuthor',
            'authorBooks',
            'publisherBooks'
        ));
    }

    public function showV2($id)
    {
        $data = $this->getBookPageData($id);
        return view('moredetail2', $data);
    }

    private function getBookPageData($id)
    {
        $book = Book::with(['category.parent', 'categories', 'primaryAuthor', 'publishingHouse'])->findOrFail($id);
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());
        $primaryAuthor = $book->primaryAuthor;

        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::where('author_id', $primaryAuthor->id)->where('id', '!=', $book->id)->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->take(10)->get();
        } else {
            $authorBooks = Book::whereHas('primaryAuthor', fn($q) => $q->where('id', $book->author_id))->where('id', '!=', $book->id)->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->take(10)->get();
        }

        $bookCatIds = $book->categories->pluck('id')->toArray();
        $relatedBooks = Book::with('primaryAuthor')->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $bookCatIds))->where('id', '!=', $book->id)->take(10)->get();
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::with('primaryAuthor')->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->whereHas('categories', fn($q) => $q->where('book_category.category_id', $book->category->parent_id))->where('id', '!=', $book->id)->take(10)->get();
        }
        if ($relatedBooks->isEmpty()) $relatedBooks = $authorBooks->take(10);
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::with('primaryAuthor')->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->where('id', '!=', $book->id)->latest()->take(10)->get();
        }

        $publisherBooks = collect();
        if ($book->publishing_house_id) {
            $publisherBooks = Book::with('primaryAuthor')->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->where('publishing_house_id', $book->publishing_house_id)->where('id', '!=', $book->id)->where('type', 'book')->take(10)->get();
        }

        // "Customers also bought" — books co-purchased with this one
        $alsoBoughtBooks = collect();
        $orderIds = DB::table('order_details')->where('book_id', $id)->pluck('order_id');
        if ($orderIds->isNotEmpty()) {
            // Fetch IDs first to avoid MySQL 5.x "LIMIT in IN-subquery" error
            $alsoBoughtIds = DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->where('book_id', '!=', $id)
                ->groupBy('book_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->pluck('book_id');

            if ($alsoBoughtIds->isNotEmpty()) {
                $alsoBoughtBooks = Book::with('primaryAuthor')
                    ->withCount('reviews')
                    ->withAvg('reviews as reviews_avg_rating', 'rating')
                    ->whereIn('id', $alsoBoughtIds)
                    ->get();
            }
        }

        // Current user's shelf status for this book
        $shelfStatus = null;
        if (auth()->check()) {
            $shelf = \App\Models\ReadingShelf::where('user_id', auth()->id())
                ->where('book_id', $id)
                ->first();
            $shelfStatus = $shelf?->status;
        }

        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_diff($recentlyViewed, [$id]);
        array_unshift($recentlyViewed, (int) $id);
        session()->put('recently_viewed', array_slice($recentlyViewed, 0, 10));

        return compact('book', 'relatedBooks', 'authors', 'publishingHouses', 'primaryAuthor', 'authorBooks', 'publisherBooks', 'alsoBoughtBooks', 'shelfStatus');
    }

    public function getProductsApi(Request $request)
    {
        // Get search and status filters from request
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        // Start query
        $query = Book::with('category');

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
        $product = Book::with('category')->find($id);

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



public function addProduct(Request $request)
{
    $validated = $request->validate([
        'productName' => 'required|string|max:255',
        'productauthor' => 'required|string|max:255',
        'productDescription' => 'required|string',
        'productPrice' => 'required|numeric|min:0',
        'productNumPages' => 'nullable|integer|min:1',
        'productLanguage' => 'nullable|string|max:100',
        'ProductPublishingHouse' => 'nullable|string|max:255',
        'productIsbn' => 'nullable|string|max:50',
        'categories' => 'required|array|min:1',
        'categories.*' => 'exists:categories,id',
        'primary_category_id' => 'required|in_array:categories.*',
        'productQuantity' => 'required|integer|min:0',
        'productImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',
        'auto_enrich' => 'nullable|boolean'
    ]);

    $imagePath = null;

    if ($request->hasFile('productImage')) {
        try {
            $imagePath = $this->adminService->processBookImage($request->file('productImage'));
        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Image upload failed'], 500);
        }
    }

    try {
        $author = $this->adminService->findOrCreateAuthor($validated['productauthor']);
        $publishingHouseId = $this->adminService->findOrCreatePublishingHouse($validated['ProductPublishingHouse'] ?? null);

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
        $product->api_data_status = 'pending';

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

    public function searchproductBooks(Request $request)
    {
        $request->validate(['query' => 'nullable|string|max:200']);

        try {
            $books = $this->searchService->search($request->input('query', ''), 10);

            return response()->json(['success' => true, 'books' => $books]);
        } catch (\Exception $e) {
            \Log::error('Error in searchBooks:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }
    // Method to show search results page
public function searchResults(Request $request)
{
    $request->validate([
        'query'        => 'nullable|string|max:200',
        'category'     => 'nullable|integer',
        'sort'         => 'nullable|in:newest,price_asc,price_desc,title',
        'language'     => 'nullable|string|max:50',
        'price_min'    => 'nullable|numeric|min:0',
        'price_max'    => 'nullable|numeric|min:0',
        'publishers'   => 'nullable|array',
        'publishers.*' => 'integer',
        'page'         => 'nullable|integer|min:1',
    ]);

    $query      = $request->input('query', '');
    $categoryId = $request->input('category');
    $sort       = $request->input('sort');

    $categories = Category::whereNull('parent_id')->with('children')->get();
    $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());

    // 1. Search
    $books = $this->searchService->search($query);
    if ($books->isNotEmpty()) {
        $books->load('categories');
    }

    // 2. Apply filters
    if ($categoryId) {
        $books = $books->filter(fn($b) => $b->categories->contains('id', (int) $categoryId));
    }
    if ($request->has('publishers')) {
        $publisherIds = collect($request->input('publishers'))->map(fn($v) => (int) $v);
        $books = $books->whereIn('publishing_house_id', $publisherIds);
    }
    if ($request->filled('language')) {
        $books = $books->where('language', $request->input('language'));
    }
    if ($request->filled('price_min')) {
        $books = $books->where('price', '>=', (float) $request->input('price_min'));
    }
    if ($request->filled('price_max')) {
        $books = $books->where('price', '<=', (float) $request->input('price_max'));
    }

    // 3. Sort
    $books = match ($sort) {
        'newest'     => $books->sortByDesc('created_at')->values(),
        'price_asc'  => $books->sortBy('price')->values(),
        'price_desc' => $books->sortByDesc('price')->values(),
        'title'      => $books->sortBy('title')->values(),
        default      => $books->values(),
    };

    // 4. Related books
    $relatedBooks = $this->searchService->getRelatedBooks($books);
    $totalCount = $books->count() + $relatedBooks->count();

    // 5. Paginate the collection
    $perPage = 12;
    $page = $request->input('page', 1);
    $paginatedBooks = new \Illuminate\Pagination\LengthAwarePaginator(
        $books->forPage($page, $perPage)->values(),
        $books->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // 6. Related categories
    $relatedCategories = collect();
    if ($categoryId) {
        $relatedCategories = $this->searchService->relatedCategories($categoryId);
    }
    if ($relatedCategories->isEmpty()) {
        $relatedCategories = $this->searchService->popularCategories();
    }

    // 7. Detect primary category from results and reorder sidebar
    $primaryCategoryId = $books->flatMap(fn($b) => $b->categories->pluck('id'))
        ->countBy()
        ->sortDesc()
        ->keys()
        ->first();

    // Find the parent category ID (in case primaryCategoryId is a child)
    $primaryParentId = null;
    if ($primaryCategoryId) {
        $primaryCat = Category::find($primaryCategoryId);
        $primaryParentId = $primaryCat?->parent_id ?? $primaryCategoryId;
    }

    // Reorder: put the primary parent category first
    if ($primaryParentId) {
        $categories = $categories->sortByDesc(fn($cat) => $cat->id == $primaryParentId)->values();
    }

    return view('search-results', [
        'books'              => $paginatedBooks,
        'allBooksCount'      => $books->count(),
        'query'              => $query,
        'relatedBooks'       => $relatedBooks,
        'count_relatedBooks' => $totalCount,
        'categories'         => $categories,
        'relatedCategories'  => $relatedCategories,
        'publishingHouses'   => $publishingHouses,
        'primaryParentId'    => $primaryParentId,
    ]);
}
public function searchBooksAjax(Request $request)
{
    $request->validate(['query' => 'nullable|string|max:200']);

    try {
        $books = $this->searchService->search($request->input('query', ''), 5);

        return response()->json(['success' => true, 'books' => $books]);
    } catch (\Exception $e) {
        \Log::error('Error in searchBooks:', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
    }
}
   

    public function index()
    {
        // Get all authors (cached 1 hour)
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());

        // Get all active publishing houses (cached 1 hour)
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());

        // Get latest books with their relationships loaded (limited for performance)
        $books = Book::with([
            'primaryAuthor',        // Load primary author via author_id
            'authors',              // Load all authors via many-to-many as backup
            'publishingHouse',      // Load publishing house
            'category'              // Load category
        ])->withCount('reviews')
          ->withAvg('reviews as reviews_avg_rating', 'rating')
          ->latest()
          ->limit(20)
          ->get();
        $popularBooks = Cache::remember('popular_books', 1800, function () {
            return Book::select(
                'books.*',
                DB::raw('COUNT(order_details.book_id) as orders_count')
            )
            ->join('order_details', 'books.id', '=', 'order_details.book_id')
            ->groupBy('books.id')
            ->orderByDesc('orders_count')
            ->with(['primaryAuthor', 'authors'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->limit(10)
            ->get();
        });

        // Get categories with children
        $categorie = Cache::remember('nav_categories', 3600, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->take(13)
                ->get();
        });

        $categorieIcons = Cache::remember('category_icons', 3600, function () {
            return Category::withIcons()
                ->inRandomOrder()
                ->limit(12)
                ->get();
        });

        // Get English books with relationships (cached 30 min)
        $englishBooks = Cache::remember('english_books', 1800, function () {
            return Book::where('language', 'English')
                ->with(['primaryAuthor', 'authors', 'publishingHouse'])
                ->withCount('reviews')
                ->withAvg('reviews as reviews_avg_rating', 'rating')
                ->latest()
                ->limit(10)
                ->get();
        });

        // Get accessories (cached 30 min)
        $accessories = Cache::remember('accessories_home', 1800, function () {
            return Book::accessories()
                ->with('primaryAuthor')
                ->withCount('reviews')
                ->withAvg('reviews as reviews_avg_rating', 'rating')
                ->limit(10)
                ->get();
        });

        // Get recently viewed books from session
        $recentlyViewedIds = session()->get('recently_viewed', []);
        $recentlyViewed = collect();
        if (!empty($recentlyViewedIds)) {
            $recentlyViewed = Book::with('primaryAuthor')
                ->whereIn('id', $recentlyViewedIds)
                ->where('type', 'book')
                ->withCount('reviews')
                ->withAvg('reviews as reviews_avg_rating', 'rating')
                ->get()
                ->sortBy(function ($book) use ($recentlyViewedIds) {
                    return array_search($book->id, $recentlyViewedIds);
                })->values();
        }

        // Get new books from followed authors/publishers (personalized)
        $fromFollows = collect();
        if (Auth::check()) {
            $userFollows = Follow::where('user_id', Auth::id())->get();
            $followedAuthorIds = $userFollows->where('followable_type', 'author')
                ->pluck('followable_id')->toArray();
            $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
                ->pluck('followable_id')->toArray();

            if (!empty($followedAuthorIds) || !empty($followedPublisherIds)) {
                $fromFollows = Book::where('status', 'active')
                    ->where('type', 'book')
                    ->where(function ($q) use ($followedAuthorIds, $followedPublisherIds) {
                        $q->whereIn('author_id', $followedAuthorIds)
                          ->orWhereIn('publishing_house_id', $followedPublisherIds);
                    })
                    ->with(['primaryAuthor', 'authors', 'publishingHouse'])
                    ->withCount('reviews')
                    ->withAvg('reviews as reviews_avg_rating', 'rating')
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get();
            }
        }

        return view('index', compact('books', 'categorie', 'englishBooks', 'authors', 'publishingHouses','popularBooks','categorieIcons','accessories','recentlyViewed','fromFollows'));
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
            $author = Author::firstOrCreate(
                ['name' => $request->author_name],
                ['status' => 'active']
            );
            $bookData['author_id'] = $author->id;
        } elseif ($request->author_id) {
            $author = Author::find($request->author_id);
        }
        
        $book = Book::create($bookData);

        // Sync category to pivot table
        if ($book->category_id) {
            $book->syncCategories([$book->category_id], $book->category_id);
        }

        // Create primary author relationship in book_authors table
        if (isset($author)) {
            BookAuthor::create([
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

    public function byCategory(Request $request, Category $category)
    {
        // Get current category and its children
        $childCategoryIds = $category->children->pluck('id')->toArray();
        $allCategoryIds = array_merge([$category->id], $childCategoryIds);

        // Start the query with category filter (via pivot)
        $query = Book::whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $allCategoryIds));

        // ✅ Apply publishing house filter
        if ($request->has('publishers')) {
            $query->whereIn('publishing_house_id', $request->input('publishers'));
        }

        // ✅ Apply language filter
        if ($request->filled('language')) {
            $query->where('language', $request->input('language'));
        }

        // ✅ Apply price range filters
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        

        // Additional data for the filters
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());
        $categories = Category::all();

        switch ($request->input('sort')) {
            case 'newest':
                $query->latest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
        }
        
        // Final result with pagination
        $books = $query->with(['primaryAuthor', 'publishingHouse'])->paginate(12)->appends($request->query());
        return view('by-category', compact(
            'books',
            'category',
            'categories',
            'authors',
            'publishingHouses'
        ));
    }

    public function showproduct(Request $request)
    {
        $search = $request->search;
        $categoryId = $request->category;

        $query = Book::with('category');

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

        return view('Dashbord_Admin.product', compact(
            'products',
            'totalProducts',
            'availableProducts',
            'totalCategories',
            'categories'
        ));
    }
    public function viewProduct($id){
        $product = Book::with('categories')->findOrFail($id);

        return response()->json($product);
    }
    public function updateProduct(Request $request, $id)
    {
        try {
            $product = Book::findOrFail($id);

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'author' => 'required|string|max:255',
                'page_num' => 'nullable|integer|min:1',
                'language' => 'nullable|string|max:100',
                'publishing_house' => 'nullable|string|max:255',
                'isbn' => 'nullable|string|max:50',
                'quantity' => 'required|integer|min:0',
                'categories' => 'nullable|array|min:1',
                'categories.*' => 'exists:categories,id',
                'primary_category_id' => 'nullable|in_array:categories.*',
                'category_id' => 'nullable|integer|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048'
            ]);

            $author = $this->adminService->findOrCreateAuthor($validated['author']);
            $publishingHouseId = $this->adminService->findOrCreatePublishingHouse($validated['publishing_house'] ?? null);
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

            // Handle categories
            if (!empty($validated['categories'])) {
                $product->category_id = $validated['primary_category_id'] ?? $validated['categories'][0];
            } elseif (isset($validated['category_id'])) {
                $product->category_id = $validated['category_id'];
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                try {
                    $product->image = $this->adminService->processBookImage($request->file('image'), $product->image);
                } catch (\Exception $imageError) {
                    \Log::error('Image upload error: ' . $imageError->getMessage());
                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'Failed to upload image: ' . $imageError->getMessage()], 500);
                    }
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
    public function searchBook(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3|max:100']);

        return response()->json(
            $this->searchService->searchForAdmin($request->query('q'))
        );
    }
}