<?php

namespace App\Observers;

use App\Models\Book;
use App\Models\Follow;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookObserver
{
    /**
     * Handle the Book "created" event.
     * Notify followers of the author/publisher about the new book.
     */
    public function created(Book $book): void
    {
        $this->forgetHomepageCaches();
        $this->forgetRelatedCaches($book);
        $this->notifyFollowers($book);
    }

    /**
     * Handle the Book "updating" event.
     * - Notify wishlist users when a book comes back in stock.
     * - Notify wishlist users when a book's price drops.
     */
    public function updating(Book $book): void
    {
        $this->forgetHomepageCaches();
        $this->forgetRelatedCaches($book);
        // Back in stock: quantity changed from 0 to > 0
        if ($book->isDirty('quantity')
            && $book->getOriginal('quantity') == 0
            && $book->quantity > 0
        ) {
            $this->notifyWishlistUsers($book, 'back_in_stock');
        }

        // Price drop: price decreased
        if ($book->isDirty('price')
            && $book->getOriginal('price') > $book->price
        ) {
            $this->notifyWishlistUsers($book, 'price_drop');
        }
    }

    public function deleted(Book $book): void
    {
        $this->forgetHomepageCaches();
        $this->forgetRelatedCaches($book);
    }

    /**
     * Bust the cached homepage carousels that can be affected by a book
     * being created, updated, or deleted.
     */
    private function forgetHomepageCaches(): void
    {
        Cache::forget('latest_books');
        Cache::forget('popular_books');
        Cache::forget('english_books');
        Cache::forget('french_books');
        Cache::forget('accessories_home');
        // A book's series_id / language change affects which series appear in
        // the language carousels and their books_count ordering.
        Cache::forget('arabic_series_home');
        Cache::forget('english_series_home');
    }

    /**
     * Bust the book:{id}:related_ids cache for this book and every peer book
     * referenced in its cached related sets — so that newly-changed data
     * propagates into peers that may have cached lists pointing at this book.
     */
    private function forgetRelatedCaches(Book $book): void
    {
        $ownKey = "book:{$book->id}:related_ids";
        $cached = Cache::get($ownKey);

        Cache::forget($ownKey);

        if (is_array($cached)) {
            $peerIds = array_unique(array_merge(
                $cached['relatedBooks']    ?? [],
                $cached['authorBooks']     ?? [],
                $cached['publisherBooks']  ?? [],
                $cached['alsoBoughtBooks'] ?? [],
                $cached['seriesBooks']     ?? [],
            ));
            foreach ($peerIds as $peerId) {
                Cache::forget("book:{$peerId}:related_ids");
            }
        }
    }

    /**
     * Notify followers of the book's author and/or publisher.
     */
    private function notifyFollowers(Book $book): void
    {
        $notifiedUserIds = [];

        // Notify author followers
        if ($book->author_id) {
            $authorFollowers = Follow::followersOf('author', $book->author_id);
            foreach ($authorFollowers as $userId) {
                $notifiedUserIds[] = $userId;
                UserNotification::newBook($userId, $book);
            }
        }

        // Notify publisher followers (avoid duplicate if user follows both)
        if ($book->publishing_house_id) {
            $publisherFollowers = Follow::followersOf('publisher', $book->publishing_house_id);
            foreach ($publisherFollowers as $userId) {
                if (!in_array($userId, $notifiedUserIds)) {
                    UserNotification::newBook($userId, $book);
                }
            }
        }
    }

    /**
     * Notify users who have this book in their wishlist.
     */
    private function notifyWishlistUsers(Book $book, string $type): void
    {
        $userIds = DB::table('wishlists')
            ->where('book_id', $book->id)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            if ($type === 'back_in_stock') {
                UserNotification::create([
                    'user_id' => $userId,
                    'type'    => 'back_in_stock',
                    'title'   => 'كتاب متوفر مجدداً',
                    'body'    => 'الكتاب «' . $book->title . '» أصبح متوفراً مجدداً في المكتبة',
                    'url'     => route('moredetail2.page', $book),
                ]);
            } elseif ($type === 'price_drop') {
                $oldPrice = $book->getOriginal('price');
                $newPrice = $book->price;
                UserNotification::create([
                    'user_id' => $userId,
                    'type'    => 'price_drop',
                    'title'   => 'انخفاض سعر كتاب',
                    'body'    => 'انخفض سعر «' . $book->title . '» من ' . $oldPrice . ' إلى ' . $newPrice . ' د.م',
                    'url'     => route('moredetail2.page', $book),
                ]);
            }
        }
    }
}
