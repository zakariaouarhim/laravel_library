<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Book_Review;
use App\Models\Follow;
use App\Models\Order;
use App\Models\UserModel;
use App\Services\UserInterestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InterestsBackfill extends Command
{
    protected $signature = 'interests:backfill {--user= : Only backfill a single user id}';

    protected $description = 'Seed user_category_interests from existing wishlists, completed orders, reviews, and follows';

    public function handle(UserInterestService $service): int
    {
        $this->info('Truncating user_interest_events to allow re-backfill...');
        DB::table('user_interest_events')->truncate();
        DB::table('user_category_interests')->truncate();

        $userQuery = UserModel::query();
        if ($uid = $this->option('user')) {
            $userQuery->where('id', $uid);
        }

        $users = $userQuery->pluck('id');
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $userId) {
            // Wishlist signals
            $wishlistBookIds = DB::table('wishlists')->where('user_id', $userId)->pluck('book_id');
            foreach ($wishlistBookIds as $bookId) {
                $book = Book::with('categories')->find($bookId);
                if ($book) {
                    $service->recordWishlist($userId, $book);
                }
            }

            // Purchase signals — only delivered orders count, to mirror real outcomes.
            $orderBookIds = DB::table('order_details')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->where('orders.user_id', $userId)
                ->whereIn('orders.status', ['delivered', 'shipped', 'processing', 'pending'])
                ->pluck('order_details.book_id')
                ->unique();

            foreach ($orderBookIds as $bookId) {
                $book = Book::with('categories')->find($bookId);
                if ($book) {
                    $service->recordPurchase($userId, $book);
                }
            }

            // Review signals
            $reviews = Book_Review::where('user_id', $userId)->get(['book_id', 'rating']);
            foreach ($reviews as $review) {
                $book = Book::with('categories')->find($review->book_id);
                if ($book) {
                    $service->recordReview($userId, $book, (int) $review->rating);
                }
            }

            // Follow signals (authors only — publishers don't fold into category interest)
            $followedAuthors = Follow::where('user_id', $userId)
                ->where('followable_type', 'author')
                ->pluck('followable_id');
            foreach ($followedAuthors as $authorId) {
                $author = \App\Models\Author::find($authorId);
                if ($author) {
                    $service->recordFollow($userId, $author);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
