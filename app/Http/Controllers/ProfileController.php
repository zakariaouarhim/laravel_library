<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UploadAvatarRequest;
use Illuminate\Http\Request;
use App\Models\Author;
use App\Models\PublishingHouse;
use App\Models\Book_Review;
use App\Models\Quote;
use App\Models\ReadingGoal;
use App\Models\Order;
use App\Models\Follow;
use App\Services\RecommendationService;
use App\Services\ImageService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private RecommendationService $recommendationService,
        private ImageService $imageService,
    ) {}

    public function account()
    {
        $user = auth()->user();
        $userId = auth()->id();
        $reviews = Book_Review::where('user_id', $userId)
                            ->with('book.primaryAuthor')
                            ->latest()
                            ->get();
        $lastReview = $reviews->first();
        $WishlistBook = auth()->user()
            ->wishlist()
            ->with('primaryAuthor')
            ->latest('pivot_created_at')
            ->get();
        $lastWishlistBook = $WishlistBook->first();
        $booksRead = Book_Review::where('user_id', $userId)
            ->where('is_read', 1)
            ->with('book')
            ->count();

        $readingGoal = ReadingGoal::where('user_id', $userId)
                          ->where('year', now()->year)
                          ->first();

        $target = $readingGoal?->target ?? 12;
        $progressPercent = min(100, intval(($booksRead / $target) * 100));
        $recommendations = $this->recommendationService->getRecommendations($userId);
        $wishlistBookIds = $WishlistBook->pluck('id')->toArray();
        $quotes = Quote::where('user_id', $userId)
                            ->with('book')
                            ->latest()
                            ->get();
        $lastQuote = auth()->user()->latestQuote;

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

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $user = Auth::user();

        $user->avatar = $this->imageService->processAvatar(
            $request->file('avatar'),
            $user->avatar
        );
        $user->save();

        session(['user_avatar' => $user->avatar]);

        return redirect()->route('account.page')->with('success', 'تم تحديث الصورة الشخصية بنجاح');
    }
}
