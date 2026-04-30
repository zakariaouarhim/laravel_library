<?php

namespace App\Services;

use App\Models\Author;
use App\Models\Book;
use App\Models\UserCategoryInterest;
use App\Models\UserInterestEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class UserInterestService
{
    private const WEIGHTS = [
        'view'             => 2,
        'wishlist'         => 6,
        'purchase_primary' => 12,
        'purchase_other'   => 8,
        'follow'           => 5,
        'review_pos'       => 8,
        'review_neg'       => -4,
    ];

    /**
     * View tracking is high-volume; protect against bot/refresh hammering.
     * 30 view-events per user per minute is plenty for genuine browsing.
     */
    private const VIEW_RATE_LIMIT = 30;

    public function recordView(int $userId, Book $book): void
    {
        if (RateLimiter::tooManyAttempts("interest:view:{$userId}", self::VIEW_RATE_LIMIT)) {
            return;
        }
        RateLimiter::hit("interest:view:{$userId}", 60);

        if ($this->loggedRecently($userId, 'view', 'book', $book->id, hours: 24)) {
            return;
        }

        $this->logEvent($userId, 'view', 'book', $book->id);
        $this->bump($userId, $this->categoryIdsOf($book), self::WEIGHTS['view']);
    }

    public function recordWishlist(int $userId, Book $book): void
    {
        if ($this->alreadyLogged($userId, 'wishlist', 'book', $book->id)) {
            return;
        }

        $this->logEvent($userId, 'wishlist', 'book', $book->id);
        $this->bump($userId, $this->categoryIdsOf($book), self::WEIGHTS['wishlist']);
    }

    public function recordPurchase(int $userId, Book $book): void
    {
        if ($this->loggedRecently($userId, 'purchase', 'book', $book->id, days: 30)) {
            return;
        }

        $this->logEvent($userId, 'purchase', 'book', $book->id);

        $book->loadMissing('categories');

        $primaryId = $book->categories->firstWhere('pivot.is_primary', true)?->id;
        $otherIds  = $book->categories
            ->where('pivot.is_primary', false)
            ->pluck('id')
            ->all();

        if ($primaryId) {
            $this->bump($userId, [$primaryId], self::WEIGHTS['purchase_primary']);
        }
        if (!empty($otherIds)) {
            $this->bump($userId, $otherIds, self::WEIGHTS['purchase_other']);
        }
    }

    public function recordFollow(int $userId, Author $author): void
    {
        if ($this->alreadyLogged($userId, 'follow', 'author', $author->id)) {
            return;
        }

        $this->logEvent($userId, 'follow', 'author', $author->id);

        $catIds = DB::table('book_category')
            ->join('books', 'books.id', '=', 'book_category.book_id')
            ->where('books.author_id', $author->id)
            ->whereNull('books.deleted_at')
            ->distinct()
            ->pluck('book_category.category_id')
            ->all();

        $this->bump($userId, $catIds, self::WEIGHTS['follow']);
    }

    /**
     * Edit-aware: revert prior delta and apply new one when a review is updated
     * (the prior event row stores the rating it was credited for).
     */
    public function recordReview(int $userId, Book $book, ?int $rating): void
    {
        $prior = UserInterestEvent::where([
            'user_id'      => $userId,
            'action'       => 'review',
            'subject_type' => 'book',
            'subject_id'   => $book->id,
        ])->first();

        $oldDelta = $prior ? $this->reviewWeight($prior->rating_value) : 0;
        $newDelta = $this->reviewWeight($rating);
        $diff     = $newDelta - $oldDelta;

        if ($diff !== 0) {
            $this->bump($userId, $this->categoryIdsOf($book), $diff);
        }

        if ($prior) {
            $prior->update(['rating_value' => $rating]);
        } else {
            $this->logEvent($userId, 'review', 'book', $book->id, ['rating_value' => $rating]);
        }
    }

    /**
     * Reverse a review's prior contribution (called when a review is deleted).
     */
    public function revertReview(int $userId, int $bookId): void
    {
        $prior = UserInterestEvent::where([
            'user_id'      => $userId,
            'action'       => 'review',
            'subject_type' => 'book',
            'subject_id'   => $bookId,
        ])->first();

        if (!$prior) {
            return;
        }

        $oldDelta = $this->reviewWeight($prior->rating_value);
        if ($oldDelta !== 0) {
            $book = Book::with('categories')->find($bookId);
            if ($book) {
                $this->bump($userId, $this->categoryIdsOf($book), -$oldDelta);
            }
        }

        $prior->delete();
    }

    private function reviewWeight(?int $rating): int
    {
        if ($rating === null) {
            return 0;
        }
        if ($rating >= 4) {
            return self::WEIGHTS['review_pos'];
        }
        if ($rating <= 2) {
            return self::WEIGHTS['review_neg'];
        }
        return 0;
    }

    private function categoryIdsOf(Book $book): array
    {
        $book->loadMissing('categories');
        return $book->categories->pluck('id')->all();
    }

    /**
     * Single-query bulk upsert across all categories.
     * Score column accumulates via `score + VALUES(score)` on duplicate key.
     */
    private function bump(int $userId, array $categoryIds, int $delta): void
    {
        if (empty($categoryIds) || $delta === 0) {
            return;
        }

        $now  = now();
        $rows = array_map(fn($catId) => [
            'user_id'             => $userId,
            'category_id'         => $catId,
            'score'               => $delta,
            'last_interaction_at' => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ], $categoryIds);

        DB::table('user_category_interests')->upsert(
            $rows,
            ['user_id', 'category_id'],
            [
                'score'               => DB::raw('score + VALUES(score)'),
                'last_interaction_at' => DB::raw('VALUES(last_interaction_at)'),
                'updated_at'          => DB::raw('VALUES(updated_at)'),
            ]
        );

        Cache::forget("user:{$userId}:top_cats");
        Cache::forget("user:{$userId}:recs");
    }

    private function logEvent(int $userId, string $action, string $type, int $id, array $extra = []): void
    {
        UserInterestEvent::create(array_merge([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $type,
            'subject_id'   => $id,
        ], $extra));
    }

    private function alreadyLogged(int $userId, string $action, string $type, int $id): bool
    {
        return UserInterestEvent::where([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $type,
            'subject_id'   => $id,
        ])->exists();
    }

    private function loggedRecently(
        int $userId,
        string $action,
        string $type,
        int $id,
        int $hours = 0,
        int $days = 0
    ): bool {
        $cutoff = now()->subHours($hours)->subDays($days);

        return UserInterestEvent::where([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $type,
            'subject_id'   => $id,
        ])->where('created_at', '>=', $cutoff)->exists();
    }
}
