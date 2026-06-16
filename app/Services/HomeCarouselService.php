<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use App\Models\Follow;
use App\Models\HomeCarousel;
use App\Models\Series;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the ordered set of homepage carousels (built-in "system" rows + custom
 * admin rows) into renderable payloads. Built-in resolution logic lives here, keyed
 * by HomeCarousel::SYSTEM. Non-personalized payloads are cached as one blob and
 * invalidated by admin edits (AdminHomeCarouselController::forgetCache), book changes
 * (BookObserver) and imports.
 */
class HomeCarouselService
{
    public const CACHE_KEY = 'home_carousels_resolved';

    public function __construct(private RecommendationService $recommendations) {}

    /**
     * @return Collection<int, object{id:int, dom_id:string, title:string, render:string, payload:Collection}>
     */
    public function resolveForHomepage(): Collection
    {
        $carousels = HomeCarousel::where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Heavy, non-personalized payloads (global system + custom) cached together.
        $globalPayloads = Cache::remember(self::CACHE_KEY, 1800, function () use ($carousels) {
            $map = [];
            foreach ($carousels as $c) {
                if ($this->groupOf($c) === 'global') {
                    $map[$c->id] = $this->resolveGlobal($c);
                }
            }
            return $map;
        });

        return $carousels->map(function (HomeCarousel $c) use ($globalPayloads) {
            $payload = match ($this->groupOf($c)) {
                'personalized', 'session' => $this->resolvePersonalized($c),
                default                   => $globalPayloads[$c->id] ?? collect(),
            };

            return (object) [
                'id'      => $c->id,
                'dom_id'  => $this->domIdOf($c),
                'title'   => $c->title,
                'render'  => $this->renderOf($c),
                'payload' => $payload,
            ];
        })->filter(fn($c) => $c->payload instanceof Collection && $c->payload->isNotEmpty())
          ->values();
    }

    // ----- carousel metadata helpers -----

    private function groupOf(HomeCarousel $c): string
    {
        return $c->is_system ? (HomeCarousel::SYSTEM[$c->system_key]['group'] ?? 'global') : 'global';
    }

    private function renderOf(HomeCarousel $c): string
    {
        return $c->is_system ? (HomeCarousel::SYSTEM[$c->system_key]['render'] ?? 'books') : 'books';
    }

    private function domIdOf(HomeCarousel $c): string
    {
        return $c->is_system ? (HomeCarousel::SYSTEM[$c->system_key]['dom_id'] ?? 'home-carousel-' . $c->id) : 'home-carousel-' . $c->id;
    }

    // ----- global (cacheable) resolution -----

    private function resolveGlobal(HomeCarousel $c): Collection
    {
        if (!$c->is_system) {
            return $c->resolveBooks();
        }

        return match ($c->system_key) {
            'new_arrivals'     => $this->newArrivals($c),
            'popular'          => $this->popular($c),
            'accessories'      => $this->accessories($c),
            'english_books'    => $this->booksByLanguage('English', $c),
            'french_books'     => $this->booksByLanguage('French', $c),
            'arabic_series'    => $this->series('Arabic', $c),
            'english_series'   => $this->series('English', $c),
            'categories_strip' => $this->categoriesStrip($c),
            default            => collect(),
        };
    }

    /** Base book query with the standard eager-loads + the availability toggle. */
    private function baseBookQuery(HomeCarousel $c)
    {
        $query = Book::where('type', 'book')
            ->standardOnly()
            ->with(['primaryAuthor', 'authors', 'publishingHouse', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating');

        if (!$c->show_unavailable) {
            $query->where('quantity', '>', 0);
        }

        return $query;
    }

    private function newArrivals(HomeCarousel $c): Collection
    {
        return $this->baseBookQuery($c)
            ->with(['category', 'series'])
            ->latest()
            ->limit($c->book_limit)
            ->get();
    }

    private function booksByLanguage(string $language, HomeCarousel $c): Collection
    {
        return $this->baseBookQuery($c)
            ->where('language', $language)
            ->latest()
            ->limit($c->book_limit)
            ->get();
    }

    private function accessories(HomeCarousel $c): Collection
    {
        $query = Book::accessories()
            ->with('primaryAuthor')
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating');

        if (!$c->show_unavailable) {
            $query->where('quantity', '>', 0);
        }

        return $query->limit($c->book_limit)->get();
    }

    /**
     * Best-sellers by units sold, widening the window (1→7→14→30→60→all-time)
     * until the carousel fills. (Ported from BookController.)
     */
    private function popular(HomeCarousel $c): Collection
    {
        $limit = $c->book_limit;

        $bestSellersWithin = function (?int $days) use ($limit, $c) {
            $query = Book::select('books.*', DB::raw('SUM(order_details.quantity) as orders_count'))
                ->join('order_details', 'books.id', '=', 'order_details.book_id')
                ->where('books.type', 'book')
                ->where('books.product_type', 'standard');

            if (!$c->show_unavailable) {
                $query->where('books.quantity', '>', 0);
            }
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

        return $bestSellersWithin(null);
    }

    private function series(string $language, HomeCarousel $c): Collection
    {
        return Series::inLanguage($language)
            ->with(['author', 'bundle'])
            ->withCount('books')
            ->orderByDesc('books_count')
            ->limit($c->book_limit)
            ->get();
    }

    private function categoriesStrip(HomeCarousel $c): Collection
    {
        return Category::withIcons()
            ->inRandomOrder()
            ->limit($c->book_limit)
            ->get();
    }

    // ----- personalized / session resolution (live, per request) -----

    private function resolvePersonalized(HomeCarousel $c): Collection
    {
        return match ($c->system_key) {
            'recommended'     => $this->recommended($c),
            'from_follows'    => $this->fromFollows($c),
            'recently_viewed' => $this->recentlyViewed($c),
            default           => collect(),
        };
    }

    private function recommended(HomeCarousel $c): Collection
    {
        return Auth::check()
            ? $this->recommendations->getScoredRecommendations(Auth::id(), $c->book_limit)
            : collect();
    }

    private function fromFollows(HomeCarousel $c): Collection
    {
        if (!Auth::check()) {
            return collect();
        }

        $follows = Follow::where('user_id', Auth::id())->get();
        $authorIds = $follows->where('followable_type', 'author')->pluck('followable_id')->toArray();
        $publisherIds = $follows->where('followable_type', 'publisher')->pluck('followable_id')->toArray();

        if (empty($authorIds) && empty($publisherIds)) {
            return collect();
        }

        return $this->baseBookQuery($c)
            ->where('status', 'active')
            ->where(function ($q) use ($authorIds, $publisherIds) {
                $q->whereIn('author_id', $authorIds)
                  ->orWhereIn('publishing_house_id', $publisherIds);
            })
            ->orderByDesc('created_at')
            ->limit($c->book_limit)
            ->get();
    }

    private function recentlyViewed(HomeCarousel $c): Collection
    {
        $ids = session()->get('recently_viewed', []);
        if (empty($ids)) {
            return collect();
        }

        return $this->baseBookQuery($c)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn($book) => array_search($book->id, $ids))
            ->take($c->book_limit)
            ->values();
    }
}
