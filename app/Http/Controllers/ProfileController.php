<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserModel;
use App\Models\Book;
use App\Models\Author;
use App\Models\PublishingHouse;
use App\Models\Book_Review;
use App\Models\Quote;
use App\Models\ReadingGoal;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Follow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProfileController extends Controller
{
    public function account()
    {
        $user = auth()->user();
        $userId = auth()->id();
        $reviews = Book_Review::where('user_id', $userId)
                            ->with('book')
                            ->latest()
                            ->get();
        $lastReview = Book_Review::where('user_id', $userId)
                         ->with('book')
                         ->latest()
                         ->first();
        $lastWishlistBook = auth()->user()
            ->wishlist()
            ->latest('pivot_created_at')
            ->first();
        $wishlist = auth()->user()->wishlist();
        $booksRead = Book_Review::where('user_id', $userId)
            ->where('is_read', 1)
            ->with('book')
            ->count();

        $readingGoal = ReadingGoal::where('user_id', $userId)
                          ->where('year', now()->year)
                          ->first();

        $target = $readingGoal?->target ?? 12;
        $progressPercent = min(100, intval(($booksRead / $target) * 100));
        $recommendations = $this->getRecommendations($userId);
        $wishlistBookIds = auth()->check()
            ? auth()->user()->wishlist()->pluck('books.id')->toArray()
            : [];
        $quotes = Quote::where('user_id', $userId)
                            ->with('book')
                            ->latest()
                            ->get();
        $lastQuote = auth()->user()->latestQuote;

        $WishlistBook = auth()->user()
            ->wishlist()
            ->latest('pivot_created_at')
            ->get();
        $OrderNumber = Order::where('user_id', $userId)->count();

        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthors = Author::whereIn('id',
            $userFollows->where('followable_type', 'author')->pluck('followable_id')
        )->get();
        $followedPublishers = PublishingHouse::whereIn('id',
            $userFollows->where('followable_type', 'publisher')->pluck('followable_id')
        )->get();

        $reviewCount = $reviews->count();
        $quoteCount  = $quotes->count();

        return view('account',
            compact('reviews',
                'recommendations',
                'wishlistBookIds',
                'lastReview',
                'lastWishlistBook',
                'booksRead',
                'progressPercent',
                'readingGoal',
                'quotes',
                'lastQuote',
                'WishlistBook',
                'target',
                'OrderNumber',
                'followedAuthors',
                'followedPublishers',
                'reviewCount',
                'quoteCount'
            ));
    }

    public function updateReadingGoal(Request $request)
    {
        $request->validate([
            'target' => 'required|integer|min:1|max:1000',
        ]);

        ReadingGoal::updateOrCreate(
            ['user_id' => auth()->id(), 'year' => now()->year],
            ['target' => $request->target]
        );

        return back()->with('success', 'تم تحديث هدف القراءة بنجاح');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048|dimensions:max_width=2000,max_height=2000',
        ], [
            'avatar.required' => 'يرجى اختيار صورة',
            'avatar.image' => 'الملف يجب أن يكون صورة',
            'avatar.mimes' => 'الصورة يجب أن تكون بصيغة jpeg, jpg, png أو webp',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميغابايت',
            'avatar.dimensions' => 'أبعاد الصورة يجب ألا تتجاوز 2000x2000 بكسل',
        ]);

        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $image = Image::read($request->file('avatar'));
        $image->scale(width: 300);
        $encoded = $image->toWebp(80);

        $filename = 'avatars/' . uniqid('avatar_') . '.webp';
        Storage::disk('public')->put($filename, (string) $encoded);

        $user->avatar = $filename;
        $user->save();

        session(['user_avatar' => $filename]);

        return redirect()->route('account.page')->with('success', 'تم تحديث الصورة الشخصية بنجاح');
    }

    private function getRecommendations($userId, $limit = 5)
    {
        $reviewedBookIds = Book_Review::where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $wishlistBookIds = DB::table('wishlists')
            ->where('user_id', $userId)
            ->pluck('book_id')->toArray();

        $excludeIds = array_unique(array_merge($reviewedBookIds, $wishlistBookIds));

        $reviewCategories = Book_Review::where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->with('book.category')
            ->get()
            ->pluck('book.category.id')
            ->filter()->unique()->values();

        $wishlistCategories = DB::table('wishlists')
            ->join('books', 'wishlists.book_id', '=', 'books.id')
            ->where('wishlists.user_id', $userId)
            ->whereNotNull('books.category_id')
            ->pluck('books.category_id')
            ->unique()->values();

        $userFollows = Follow::where('user_id', $userId)->get();
        $followedAuthorIds = $userFollows->where('followable_type', 'author')
            ->pluck('followable_id')->toArray();
        $followedPublisherIds = $userFollows->where('followable_type', 'publisher')
            ->pluck('followable_id')->toArray();

        $allCategories = $reviewCategories->merge($wishlistCategories)
            ->unique()->take(5);

        $recommendations = collect();

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

        if ($recommendations->isEmpty()) {
            $recommendations = Book::whereNotIn('id', $excludeIds)
                ->where('status', 'active')
                ->with(['category', 'primaryAuthor'])
                ->latest()
                ->take($limit)
                ->get();
        }

        return $recommendations->map(function($book) {
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
