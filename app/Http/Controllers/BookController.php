<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookSearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Follow;
use App\Models\Series;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function __construct(
        private BookSearchService $searchService,
    ) {}

    public function show($id)
    {
        // Get the book with its category, category's parent, and author relationship
        $book = Book::with(['category.parent', 'categories', 'primaryAuthor', 'publishingHouse', 'reviewsWithUsers', 'quotesWithUsers'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->findOrFail($id);

        // Get all authors (cached 1 hour)
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());

        // Get all active publishing houses (cached 1 hour)
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());

        // Get the primary author of this book (if using relationship)
        $primaryAuthor = $book->primaryAuthor;

        // Get other books by the same author
        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::with('primaryAuthor')
                ->where('author_id', $primaryAuthor->id)
                ->where('id', '!=', $book->id)
                ->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')
                ->take(10)
                ->get();
        } else {
            // If no author relationship, try to find books by author name
            $authorBooks = Book::with('primaryAuthor')
                ->whereHas('primaryAuthor', fn($q) => $q->where('id', $book->author_id))
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
        $book = Book::with([
                'category.parent', 'categories', 'primaryAuthor', 'publishingHouse', 'series',
                'reviewsWithUsers', 'quotesWithUsers',
                'bundles' => fn($q) => $q->select('books.id', 'books.title', 'books.price', 'books.image', 'books.series_id', 'books.quantity'),
                'items'   => fn($q) => $q->orderBy('volume_number'),
            ])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->findOrFail($id);
        $authors = Cache::remember('active_authors', 3600, fn() => Author::active()->get());
        $publishingHouses = Cache::remember('active_publishers', 3600, fn() => PublishingHouse::active()->get());
        $primaryAuthor = $book->primaryAuthor;

        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->where('author_id', $primaryAuthor->id)->where('id', '!=', $book->id)->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->take(10)->get();
        } else {
            $authorBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->whereHas('primaryAuthor', fn($q) => $q->where('id', $book->author_id))->where('id', '!=', $book->id)->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->take(10)->get();
        }

        $bookCatIds = $book->categories->pluck('id')->toArray();
        $relatedBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $bookCatIds))->where('id', '!=', $book->id)->take(10)->get();
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->whereHas('categories', fn($q) => $q->where('book_category.category_id', $book->category->parent_id))->where('id', '!=', $book->id)->take(10)->get();
        }
        if ($relatedBooks->isEmpty()) $relatedBooks = $authorBooks->take(10);
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->where('id', '!=', $book->id)->latest()->take(10)->get();
        }

        $publisherBooks = collect();
        if ($book->publishing_house_id) {
            $publisherBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])->standardOnly()->withCount('reviews')->withAvg('reviews as reviews_avg_rating', 'rating')->where('publishing_house_id', $book->publishing_house_id)->where('id', '!=', $book->id)->where('type', 'book')->take(10)->get();
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
                $alsoBoughtBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])
                    ->standardOnly()
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

        // Other books in the same series
        $seriesBooks = collect();
        if ($book->series_id) {
            $seriesBooks = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])
                ->where('series_id', $book->series_id)
                ->where('id', '!=', $book->id)
                ->orderBy('volume_number')
                ->withCount('reviews')
                ->withAvg('reviews as reviews_avg_rating', 'rating')
                ->get();
        }

        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_diff($recentlyViewed, [$id]);
        array_unshift($recentlyViewed, (int) $id);
        session()->put('recently_viewed', array_slice($recentlyViewed, 0, 10));

        return compact('book', 'relatedBooks', 'authors', 'publishingHouses', 'primaryAuthor', 'authorBooks', 'publisherBooks', 'alsoBoughtBooks', 'seriesBooks', 'shelfStatus');
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

        // 1. Build search query (no execution yet)
        $builder = $this->searchService->searchQuery($query);
        $builder->with(['primaryAuthor', 'publishingHouse', 'categories', 'category', 'bundles:id,title,price,image']);

        // 2. Apply DB-level filters
        $builder = $this->applySearchFilters($builder, $request);

        // 3. Sort at DB level
        $builder = match ($sort) {
            'newest'     => $builder->orderByDesc('created_at'),
            'price_asc'  => $builder->orderBy('price'),
            'price_desc' => $builder->orderByDesc('price'),
            'title'      => $builder->orderBy('title'),
            default      => $builder->orderByDesc('created_at'),
        };

        $totalSearchCount = $builder->count();

        // 5. DB-level pagination
        $paginatedBooks = $builder->paginate(12)->appends($request->query());

        // 6. Related books (from current page results)
        $relatedBooks = $this->searchService->getRelatedBooks($paginatedBooks->getCollection());
        $totalCount = $totalSearchCount + $relatedBooks->count();

        // 7. Related categories
        $relatedCategories = collect();
        if ($categoryId) {
            $relatedCategories = $this->searchService->relatedCategories($categoryId);
        }
        if ($relatedCategories->isEmpty()) {
            $relatedCategories = $this->searchService->popularCategories();
        }

        // 8. Detect primary category from current page and reorder sidebar
        $primaryCategoryId = $paginatedBooks->getCollection()
            ->flatMap(fn($b) => $b->categories->pluck('id'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        $primaryParentId = null;
        if ($primaryCategoryId) {
            $primaryCat = Category::find($primaryCategoryId);
            $primaryParentId = $primaryCat?->parent_id ?? $primaryCategoryId;
        }

        if ($primaryParentId) {
            $categories = $categories->sortByDesc(fn($cat) => $cat->id == $primaryParentId)->values();
        }

        return view('search-results', [
            'books'              => $paginatedBooks,
            'allBooksCount'      => $totalSearchCount,
            'query'              => $query,
            'relatedBooks'       => $relatedBooks,
            'count_relatedBooks' => $totalCount,
            'categories'         => $categories,
            'relatedCategories'  => $relatedCategories,
            'publishingHouses'   => $publishingHouses,
            'primaryParentId'    => $primaryParentId,
        ]);
    }

    private function applySearchFilters($builder, Request $request)
    {
        $builder->where('product_type', 'standard');
        if ($request->input('category')) {
            $builder->whereHas('categories', fn($q) => $q->where('categories.id', (int) $request->input('category')));
        }
        if ($request->has('publishers')) {
            $builder->whereIn('publishing_house_id', $request->input('publishers'));
        }
        if ($request->filled('language')) {
            $builder->where('language', $request->input('language'));
        }
        if ($request->filled('price_min')) {
            $builder->where('price', '>=', (float) $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $builder->where('price', '<=', (float) $request->input('price_max'));
        }
        return $builder;
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

        // Get latest books with their relationships loaded (cached 5 min)
        $books = Cache::remember('latest_books', 300, function () {
            return Book::with([
                'primaryAuthor',
                'authors',
                'publishingHouse',
                'category',
                'series',
                'bundles:id,title,price,image',
            ])->withCount('reviews')
              ->withAvg('reviews as reviews_avg_rating', 'rating')
              ->where('type', 'book')
              ->standardOnly()
              ->latest()
              ->limit(20)
              ->get();
        });
        $popularBooks = Cache::remember('popular_books', 1800, function () {
            return Book::select(
                'books.*',
                DB::raw('COUNT(order_details.book_id) as orders_count')
            )
            ->join('order_details', 'books.id', '=', 'order_details.book_id')
            ->where('books.type', 'book')
            ->where('books.product_type', 'standard')
            ->groupBy('books.id')
            ->orderByDesc('orders_count')
            ->with(['primaryAuthor', 'authors', 'bundles:id,title,price,image'])
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
                ->where('type', 'book')
                ->standardOnly()
                ->with(['primaryAuthor', 'authors', 'publishingHouse', 'bundles:id,title,price,image'])
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
            $recentlyViewed = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])
                ->whereIn('id', $recentlyViewedIds)
                ->where('type', 'book')
                ->standardOnly()
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
                    ->standardOnly()
                    ->where(function ($q) use ($followedAuthorIds, $followedPublisherIds) {
                        $q->whereIn('author_id', $followedAuthorIds)
                          ->orWhereIn('publishing_house_id', $followedPublisherIds);
                    })
                    ->with(['primaryAuthor', 'authors', 'publishingHouse', 'bundles:id,title,price,image'])
                    ->withCount('reviews')
                    ->withAvg('reviews as reviews_avg_rating', 'rating')
                    ->orderByDesc('created_at')
                    ->limit(15)
                    ->get();
            }
        }

        $arabicSeries = Cache::remember('arabic_series_home', 1800, function () {
            return Series::inLanguage('Arabic')
                ->with(['author', 'bundle'])
                ->withCount('books')
                ->orderByDesc('books_count')
                ->limit(10)
                ->get();
        });

        $englishSeries = Cache::remember('english_series_home', 1800, function () {
            return Series::inLanguage('English')
                ->with(['author', 'bundle'])
                ->withCount('books')
                ->orderByDesc('books_count')
                ->limit(10)
                ->get();
        });

        return view('index', compact('books', 'categorie', 'englishBooks', 'authors', 'publishingHouses','popularBooks','categorieIcons','accessories','recentlyViewed','fromFollows','arabicSeries','englishSeries'));
    }

    public function byCategory(Request $request, Category $category)
    {
        // Get current category and its children
        $childCategoryIds = $category->children->pluck('id')->toArray();
        $allCategoryIds = array_merge([$category->id], $childCategoryIds);

        // Start the query with category filter (via pivot)
        $query = Book::standardOnly()->whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $allCategoryIds));

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
        $books = $query->with(['primaryAuthor', 'publishingHouse', 'bundles:id,title,price,image'])->paginate(12)->appends($request->query());

        // Sidebar: sibling/child categories ordered by book count, then name
        if ($category->children->count() > 0) {
            // Parent category -> show its children
            $sidebarSource = $category->children()->withCount('books');
        } elseif ($category->parent) {
            // Child category -> show siblings (parent's children)
            $sidebarSource = $category->parent->children()->withCount('books');
        } else {
            $sidebarSource = null;
        }

        $displayCategories = $sidebarSource
            ? $sidebarSource->orderByDesc('books_count')->orderBy('name')->get()
            : collect();

        return view('by-category', compact(
            'books',
            'category',
            'categories',
            'authors',
            'publishingHouses',
            'displayCategories'
        ));
    }
}
