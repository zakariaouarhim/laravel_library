<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\BookViewed;
use App\Models\Book;
use App\Models\Author;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookSearchService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Follow;
use App\Models\Series;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function __construct(
        private BookSearchService $searchService,
        private RecommendationService $recommendations,
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

        if (Auth::check()) {
            BookViewed::dispatch($book, Auth::id());
        }

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

    public function showV2(Book $book)
    {
        $data = $this->getBookPageData($book->id);
        $loaded = $data['book'];

        $data['seo'] = app(\App\Services\Seo\MetaBuilder::class)->forBook($loaded);

        $schemaBuilder = app(\App\Services\Seo\SchemaBuilder::class);

        // Mirror the visible Bootstrap breadcrumb at the top of moredetail2.blade.php.
        $trail = [['label' => 'الرئيسية', 'url' => url('/')]];
        if ($loaded->category) {
            if ($loaded->category->parent) {
                $trail[] = ['label' => $loaded->category->parent->name, 'url' => route('by-category', $loaded->category->parent)];
            }
            $trail[] = ['label' => $loaded->category->name, 'url' => route('by-category', $loaded->category)];
        }
        $trail[] = ['label' => $loaded->title];

        $data['schemas'] = [
            'book'        => $schemaBuilder->forBook($loaded),
            'breadcrumbs' => $schemaBuilder->forBreadcrumbs($trail),
        ];

        // ItemList schemas for each visible carousel (only when non-empty).
        if ($data['relatedBooks']->isNotEmpty()) {
            $data['schemas']['itemlist_related'] = $schemaBuilder->forItemList($data['relatedBooks'], 1, 'كتب ذات صلة');
        }
        if ($data['seriesBooks']->isNotEmpty()) {
            $seriesName = $loaded->series?->name ?? '';
            $data['schemas']['itemlist_series'] = $schemaBuilder->forItemList(
                $data['seriesBooks'], 1, trim("باقي أجزاء {$seriesName}")
            );
        }
        if ($data['publisherBooks']->isNotEmpty()) {
            $pubName = $loaded->publishingHouse?->name ?? 'دار النشر';
            $data['schemas']['itemlist_publisher'] = $schemaBuilder->forItemList(
                $data['publisherBooks'], 1, "المزيد من {$pubName}"
            );
        }
        if ($data['alsoBoughtBooks']->isNotEmpty()) {
            $data['schemas']['itemlist_also_bought'] = $schemaBuilder->forItemList(
                $data['alsoBoughtBooks'], 1, 'عملاء آخرون اشتروا أيضاً'
            );
        }

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

        $related = $this->loadRelatedBooks($book);
        $relatedBooks    = $related['relatedBooks'];
        $authorBooks     = $related['authorBooks'];
        $publisherBooks  = $related['publisherBooks'];
        $alsoBoughtBooks = $related['alsoBoughtBooks'];
        $seriesBooks     = $related['seriesBooks'];

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

        return compact('book', 'relatedBooks', 'authors', 'publishingHouses', 'primaryAuthor', 'authorBooks', 'publisherBooks', 'alsoBoughtBooks', 'seriesBooks', 'shelfStatus');
    }

    /**
     * Returns the 5 related-book collections for a book detail page, scored,
     * deduplicated across carousels, and in-stock-first.
     *
     * Cached as ID arrays under `book:{id}:related_ids` (1h TTL) and hydrated
     * in a single query — cuts ~4-7 expensive queries down to 1.
     */
    private function loadRelatedBooks(Book $book): array
    {
        $cacheKey = "book:{$book->id}:related_ids";
        $ids = Cache::remember($cacheKey, 3600, fn() => $this->computeRelatedIds($book));

        $allIds = array_unique(array_merge(
            $ids['relatedBooks'], $ids['authorBooks'], $ids['publisherBooks'],
            $ids['alsoBoughtBooks'], $ids['seriesBooks']
        ));

        if (empty($allIds)) {
            $empty = collect();
            return [
                'relatedBooks'    => $empty, 'authorBooks' => $empty,
                'publisherBooks'  => $empty, 'alsoBoughtBooks' => $empty,
                'seriesBooks'     => $empty,
            ];
        }

        $byId = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        $resolve = fn(array $idList) => collect($idList)
            ->map(fn($id) => $byId->get($id))
            ->filter()
            ->values();

        return [
            'relatedBooks'    => $resolve($ids['relatedBooks']),
            'authorBooks'     => $resolve($ids['authorBooks']),
            'publisherBooks'  => $resolve($ids['publisherBooks']),
            'alsoBoughtBooks' => $resolve($ids['alsoBoughtBooks']),
            'seriesBooks'     => $resolve($ids['seriesBooks']),
        ];
    }

    /**
     * Compute the 5 deduplicated, in-stock-first ID arrays. Only runs on cache miss.
     */
    private function computeRelatedIds(Book $book): array
    {
        $stockSort = 'CASE WHEN quantity > 0 AND status = "active" THEN 0 ELSE 1 END';
        $bookCatIds = $book->categories->pluck('id')->toArray();
        $primaryCatId = $book->category_id;
        $usedIds = [$book->id];

        // Build candidate pool (~50): any shared category, same author, or same publisher.
        $candidates = Book::standardOnly()
            ->with('categories:id')
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->where('id', '!=', $book->id)
            ->where(function ($q) use ($bookCatIds, $book) {
                if (!empty($bookCatIds)) {
                    $q->orWhereHas('categories', fn($c) => $c->whereIn('book_category.category_id', $bookCatIds));
                }
                if ($book->author_id) {
                    $q->orWhere('author_id', $book->author_id);
                }
                if ($book->publishing_house_id) {
                    $q->orWhere('publishing_house_id', $book->publishing_house_id);
                }
            })
            ->orderByRaw($stockSort)
            ->limit(60)
            ->get();

        // Score each candidate; pick top 10 for $relatedBooks.
        $scored = $candidates->map(function ($cand) use ($primaryCatId, $bookCatIds, $book) {
            $score = 0;
            $candCatIds = $cand->categories->pluck('id')->toArray();
            if ($primaryCatId && in_array($primaryCatId, $candCatIds)) $score += 3;
            $shared = array_intersect($bookCatIds, $candCatIds);
            $score += max(0, count($shared) - ($primaryCatId && in_array($primaryCatId, $shared) ? 1 : 0));
            if ($book->author_id && $cand->author_id === $book->author_id) $score += 1;
            if ($book->publishing_house_id && $cand->publishing_house_id === $book->publishing_house_id) $score += 1;
            $inStock = ($cand->quantity ?? 0) > 0 && $cand->status === 'active';
            return [
                'id'      => $cand->id,
                'score'   => $score,
                'rating'  => (float) ($cand->reviews_avg_rating ?? 0),
                'created' => $cand->created_at?->timestamp ?? 0,
                'in_stock' => $inStock ? 1 : 0,
            ];
        });

        $relatedIds = $scored
            ->sortBy([
                ['in_stock', 'desc'],
                ['score', 'desc'],
                ['rating', 'desc'],
                ['created', 'desc'],
            ])
            ->take(10)
            ->pluck('id')
            ->all();

        $usedIds = array_merge($usedIds, $relatedIds);

        // Author books — exclude what's already in $relatedBooks.
        $authorIds = [];
        if ($book->author_id) {
            $authorIds = Book::standardOnly()
                ->where('author_id', $book->author_id)
                ->whereNotIn('id', $usedIds)
                ->orderByRaw($stockSort)
                ->latest()
                ->limit(10)
                ->pluck('id')
                ->all();
            $usedIds = array_merge($usedIds, $authorIds);
        }

        // Publisher books — exclude what's already used.
        $publisherIds = [];
        if ($book->publishing_house_id) {
            $publisherIds = Book::standardOnly()
                ->where('publishing_house_id', $book->publishing_house_id)
                ->where('type', 'book')
                ->whereNotIn('id', $usedIds)
                ->orderByRaw($stockSort)
                ->latest()
                ->limit(10)
                ->pluck('id')
                ->all();
            $usedIds = array_merge($usedIds, $publisherIds);
        }

        // "Customers also bought" — exclude full used list.
        $alsoBoughtIds = [];
        $orderIds = DB::table('order_details')->where('book_id', $book->id)->pluck('order_id');
        if ($orderIds->isNotEmpty()) {
            $candidateIds = DB::table('order_details')
                ->whereIn('order_id', $orderIds)
                ->where('book_id', '!=', $book->id)
                ->whereNotIn('book_id', $usedIds)
                ->groupBy('book_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->pluck('book_id');

            if ($candidateIds->isNotEmpty()) {
                $alsoBoughtIds = Book::standardOnly()
                    ->whereIn('id', $candidateIds)
                    ->orderByRaw($stockSort)
                    ->pluck('id')
                    ->all();
            }
        }

        // Series books — NOT deduplicated (series volumes are always relevant in their own carousel).
        $seriesIds = [];
        if ($book->series_id) {
            $seriesIds = Book::where('series_id', $book->series_id)
                ->where('id', '!=', $book->id)
                ->orderBy('volume_number')
                ->pluck('id')
                ->all();
        }

        return [
            'relatedBooks'    => $relatedIds,
            'authorBooks'     => $authorIds,
            'publisherBooks'  => $publisherIds,
            'alsoBoughtBooks' => $alsoBoughtIds,
            'seriesBooks'     => $seriesIds,
        ];
    }

    public function searchproductBooks(Request $request)
    {
        $request->validate(['query' => 'nullable|string|max:200']);

        try {
            $rawQuery = $request->input('query', '');
            $books = $this->searchService->search($rawQuery, 10);
            app(\App\Services\SearchQueryLogger::class)->log(
                $rawQuery,
                is_countable($books) ? count($books) : 0,
                'autocomplete',
                $request,
            );

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

        app(\App\Services\SearchQueryLogger::class)->log(
            $query,
            $totalSearchCount,
            'page',
            $request,
        );

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
            // Best-sellers ranked by units sold within a recent time window.
            // Widen the window (1 → 7 → 14 → 30 → 60 days → all-time) until the
            // carousel is filled, then fall back to all-time if still short.
            $limit = 10;

            $bestSellersWithin = function (?int $days) use ($limit) {
                $query = Book::select(
                        'books.*',
                        DB::raw('SUM(order_details.quantity) as orders_count')
                    )
                    ->join('order_details', 'books.id', '=', 'order_details.book_id')
                    ->where('books.type', 'book')
                    ->where('books.product_type', 'standard');

                if ($days !== null) {
                    $query->where('order_details.created_at', '>=', now()->subDays($days));
                }

                return $query->groupBy('books.id')
                    ->orderByDesc('orders_count')
                    ->with(['primaryAuthor', 'authors', 'bundles:id,title,price,image'])
                    ->withCount('reviews')
                    ->withAvg('reviews as reviews_avg_rating', 'rating')
                    ->limit($limit)
                    ->get();
            };

            foreach ([1, 7, 14, 30, 60] as $days) {
                $books = $bestSellersWithin($days);
                if ($books->count() >= $limit) {
                    return $books;
                }
            }

            // Final fallback: all-time best-sellers (no date filter).
            return $bestSellersWithin(null);
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

        // Get French books with relationships (cached 30 min)
        $frenchBooks = Cache::remember('french_books', 1800, function () {
            return Book::where('language', 'French')
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

        // Interest-score-weighted recommendations for authenticated users.
        // Returns an empty Collection for guests or users with no signal yet.
        $recommendedForYou = Auth::check()
            ? $this->recommendations->getScoredRecommendations(Auth::id(), 12)
            : collect();

        $seo = app(\App\Services\Seo\MetaBuilder::class)->forHomepage();

        $schemaBuilder = app(\App\Services\Seo\SchemaBuilder::class);
        $schemas = [
            'website' => $schemaBuilder->forWebsite(),
        ];
        $bookStore = $schemaBuilder->forBookStore();
        if (!empty($bookStore)) {
            $schemas['bookstore'] = $bookStore;
        }

        return view('index', compact('books', 'categorie', 'englishBooks', 'frenchBooks', 'authors', 'publishingHouses','popularBooks','categorieIcons','accessories','recentlyViewed','fromFollows','arabicSeries','englishSeries','recommendedForYou', 'seo', 'schemas'));
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

        $seo = app(\App\Services\Seo\MetaBuilder::class)->forCategory($category, $books->total());
        if ($books->currentPage() > 1 || $request->hasAny(['publishers', 'language', 'price_min', 'price_max', 'sort'])) {
            $seo['robots']    = 'noindex,follow';
            $seo['canonical'] = route('by-category', $category);
        }

        $schemaBuilder = app(\App\Services\Seo\SchemaBuilder::class);
        $trail = [
            ['label' => 'الرئيسية', 'url' => url('/')],
            ['label' => 'الأقسام',  'url' => route('categories.index')],
        ];
        if ($category->parent) {
            $trail[] = ['label' => $category->parent->name, 'url' => route('by-category', $category->parent)];
        }
        $trail[] = ['label' => $category->name];

        $schemas = [
            'collection'  => $schemaBuilder->forCategory($category, collect($books->items())),
            'breadcrumbs' => $schemaBuilder->forBreadcrumbs($trail),
        ];

        return view('by-category', compact(
            'books',
            'category',
            'categories',
            'authors',
            'publishingHouses',
            'displayCategories',
            'seo',
            'schemas'
        ));
    }
}
