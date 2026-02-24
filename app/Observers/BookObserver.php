<?php

namespace App\Observers;

use App\Models\Book;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

class BookObserver
{
    /**
     * Handle the Book "updating" event.
     * Notify wishlist users when a book comes back in stock.
     */
    public function updating(Book $book): void
    {
        // Check if Quantity changed from 0 to > 0
        if ($book->isDirty('Quantity')
            && $book->getOriginal('Quantity') == 0
            && $book->Quantity > 0
        ) {
            $this->notifyWishlistUsers($book);
        }
    }

    private function notifyWishlistUsers(Book $book): void
    {
        $userIds = DB::table('wishlists')
            ->where('book_id', $book->id)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            UserNotification::create([
                'user_id' => $userId,
                'type'    => 'back_in_stock',
                'title'   => 'كتاب متوفر مجدداً',
                'body'    => 'الكتاب «' . $book->title . '» أصبح متوفراً مجدداً في المكتبة',
                'url'     => route('moredetail2.page', $book->id),
            ]);
        }
    }
}
