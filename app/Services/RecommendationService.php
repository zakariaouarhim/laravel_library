<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Book_Review;
use App\Models\Follow;
use Illuminate\Support\Collection;
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

        // Categories from highly rated reviews
        $reviewCategories = Book_Review::where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->with('book.category')
            ->get()
            ->pluck('book.category.id')
            ->filter()->unique()->values();

        // Categories from wishlist
        $wishlistCategories = DB::table('wishlists')
            ->join('books', 'wishlists.book_id', '=', 'books.id')
            ->where('wishlists.user_id', $userId)
            ->whereNotNull('books.category_id')
            ->pluck('books.category_id')
            ->unique()->values();

        // Followed authors and publishers
        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthorIds = $userFollows->where('followable_type', 'author')
            ->pluck('followable_id')->toArray();
        $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
            ->pluck('followable_id')->toArray();

        $allCategories = $reviewCategories->merge($wishlistCategories)
            ->unique()->take(5);

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
                'title' => $book->title,
                'author' => $book->primaryAuthor->name ?? 'مؤلف غير محدد',
                'image' => $book->image ? asset($book->image) : asset('images/default-book.jpg'),
                'rating' => round($book->reviews_avg_rating ?? 0),
                'category' => $book->category->name ?? ''
            ];
        });
    }
}
