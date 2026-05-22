<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Book_Review;
use App\Models\Follow;
use App\Models\UserCategoryInterest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get personalized book recommendations for a user.
     *
     * Priority: followed authors/publishers -> category matches -> popular -> newest
     */
    public function getRecommendations(int $userId, int $limit = 5): Collection
    {
        $reviewedBookIds = Book_Review::where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $wishlistBookIds = DB::table('wishlists')
            ->where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $excludeIds = array_unique(array_merge($reviewedBookIds, $wishlistBookIds));

        // Categories from highly rated reviews + wishlist (single UNION query)
        $allCategories = DB::table('reviews')
            ->join('books', 'reviews.book_id', '=', 'books.id')
            ->where('reviews.user_id', $userId)
            ->where('reviews.rating', '>=', 4)
            ->whereNotNull('books.category_id')
            ->select('books.category_id')
            ->union(
                DB::table('wishlists')
                    ->join('books', 'wishlists.book_id', '=', 'books.id')
                    ->where('wishlists.user_id', $userId)
                    ->whereNotNull('books.category_id')
                    ->select('books.category_id')
            )
            ->pluck('category_id')
            ->unique()
            ->take(5);

        // Followed authors and publishers
        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthorIds = $userFollows->where('followable_type', 'author')
            ->pluck('followable_id')->toArray();
        $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
            ->pluck('followable_id')->toArray();

        $recommendations = collect();

        // 1. Books from followed authors/publishers
        if (!empty($followedAuthorIds) || !empty($followedPublisherIds)) {
            $followBooks = Book::whereNotIn('id', $excludeIds)
                ->where('status', 'active')
                ->where(function ($q) use ($followedAuthorIds, $followedPublisherIds) {
                    $q->whereIn('author_id', $followedAuthorIds)
                      ->orWhereIn('publishing_house_id', $followedPublisherIds);
                })
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('created_at')
                ->take($limit)
                ->get();

            $recommendations = $recommendations->merge($followBooks);
        }

        // 2. Books from preferred categories
        if ($recommendations->count() < $limit && $allCategories->isNotEmpty()) {
            $catBooks = Book::whereIn('category_id', $allCategories)
                ->whereNotIn('id', $excludeIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($catBooks);
        }

        // 3. Popular books fallback
        if ($recommendations->count() < $limit) {
            $popularBooks = Book::whereNotIn('id', $excludeIds)
                ->whereNotIn('id', $recommendations->pluck('id')->toArray())
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->having('reviews_count', '>', 0)
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take($limit - $recommendations->count())
                ->get();

            $recommendations = $recommendations->merge($popularBooks);
        }

        // 4. Newest books as last fallback
        if ($recommendations->isEmpty()) {
            $recommendations = Book::whereNotIn('id', $excludeIds)
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->latest()
                ->take($limit)
                ->get();
        }

        return $recommendations->map(function ($book) {
            return [
                'id' => $book->id,
                'slug' => $book->slug,
                'title' => $book->title,
                'author_name' => $book->primaryAuthor->name ?? 'مؤلف غير محدد',
                'image' => $book->image ? asset($book->image) : asset('images/default-book.jpg'),
                'rating' => round($book->reviews_avg_rating ?? 0),
                'category' => $book->category->name ?? ''
            ];
        });
    }

    /**
     * Interest-score-weighted recommendations. Falls back to the rules-based
     * getRecommendations() when the user has no interest data yet.
     *
     * Returns a Book Collection (not the DTO array) so callers can render with
     * the standard book-card partials.
     */
    public function getScoredRecommendations(int $userId, int $limit = 12): Collection
    {
        return Cache::remember("user:{$userId}:recs", 1800, function () use ($userId, $limit) {
            $topCats = UserCategoryInterest::where('user_id', $userId)
                ->where('score', '>', 0)
                ->orderByDesc('score')
                ->limit(8)
                ->pluck('score', 'category_id');

            if ($topCats->isEmpty()) {
                // No signal yet — caller is responsible for the rules-based fallback.
                return collect();
            }

            $excludeIds = $this->collectExcludedBookIds($userId);

            $candidates = Book::query()
                ->standardOnly()
                ->where('status', 'active')
                // Available individually OR via at least one bundle —
                // matches the badge logic in partials/book-card-grid.blade.php.
                ->where(function ($q) {
                    $q->where('quantity', '>', 0)
                      ->orWhereHas('bundles');
                })
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $topCats->keys()))
                ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                ->with(['primaryAuthor', 'category', 'categories:id', 'bundles:id,title,price,image'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->limit($limit * 5)
                ->get();

            return $candidates
                ->map(function ($book) use ($topCats) {
                    $score = $book->categories->sum(fn($c) => (float) ($topCats[$c->id] ?? 0));
                    // Tie-break with average rating so a 5★ book beats an unrated one at the same score.
                    $book->setAttribute(
                        '_interest_score',
                        $score + (((float) ($book->reviews_avg_rating ?? 0)) / 5)
                    );
                    return $book;
                })
                ->sortByDesc('_interest_score')
                ->take($limit)
                ->values();
        });
    }

    private function collectExcludedBookIds(int $userId): array
    {
        return DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->where('orders.user_id', $userId)
            ->pluck('order_details.book_id')
            ->merge(DB::table('wishlists')->where('user_id', $userId)->pluck('book_id'))
            ->merge(DB::table('hidden_recommendations')->where('user_id', $userId)->pluck('book_id'))
            ->merge(Book_Review::where('user_id', $userId)->pluck('book_id'))
            ->unique()
            ->values()
            ->all();
    }
}
