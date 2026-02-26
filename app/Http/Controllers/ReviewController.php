<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book_Review;
use App\Models\Book;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'is_read' => 'nullable|boolean',
        ]);

        // Check if user already reviewed this book
        $existingReview = Book_Review::where('user_id', auth()->id())
                                   ->where('book_id', $request->book_id)
                                   ->first();

        if ($existingReview) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لقد قمت بتقييم هذا الكتاب من قبل.'], 422);
            }
            return back()->with('error', 'لقد قمت بتقييم هذا الكتاب من قبل.');
        }

        $review = Book_Review::create([
            'user_id' => auth()->id(),
            'book_id' => $request->book_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_read' => $request->has('is_read') ? 1 : 0,
        ]);

        if ($request->ajax()) {
            $review->load('user');
            $book = Book::find($request->book_id);
            $avgRating = Book_Review::where('book_id', $request->book_id)->avg('rating');
            $reviewsCount = Book_Review::where('book_id', $request->book_id)->count();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال تقييمك بنجاح.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'is_read' => $review->is_read,
                    'created_at' => $review->created_at->diffForHumans(),
                    'user_name' => $review->user->name ?? 'مستخدم',
                    'user_initial' => mb_substr($review->user->name ?? 'م', 0, 1),
                ],
                'summary' => [
                    'avg_rating' => round($avgRating, 1),
                    'reviews_count' => $reviewsCount,
                ],
            ]);
        }

        return back()->with('success', 'تم إرسال تقييمك بنجاح.');
    }

    public function update(Request $request, Book_Review $review)
    {
        if (auth()->id() !== $review->user_id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
            }
            abort(403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        if ($request->ajax()) {
            $avgRating = Book_Review::where('book_id', $review->book_id)->avg('rating');
            $reviewsCount = Book_Review::where('book_id', $review->book_id)->count();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث تقييمك بنجاح.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                ],
                'summary' => [
                    'avg_rating' => round($avgRating, 1),
                    'reviews_count' => $reviewsCount,
                ],
            ]);
        }

        return back()->with('success', 'تم تحديث تقييمك بنجاح.');
    }

    public function destroy(Request $request, Book_Review $review)
    {
        if (auth()->id() !== $review->user_id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
            }
            abort(403);
        }

        $bookId = $review->book_id;
        $review->delete();

        if ($request->ajax()) {
            $avgRating = Book_Review::where('book_id', $bookId)->avg('rating') ?? 0;
            $reviewsCount = Book_Review::where('book_id', $bookId)->count();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف تقييمك.',
                'summary' => [
                    'avg_rating' => round($avgRating, 1),
                    'reviews_count' => $reviewsCount,
                ],
            ]);
        }

        return back()->with('success', 'تم حذف تقييمك.');
    }

    public function toggleHelpful(Request $request, Book_Review $review)
    {
        $userId = auth()->id();

        $existing = \App\Models\ReviewLike::where('review_id', $review->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $review->decrement('likes_count');
            $liked = false;
        } else {
            \App\Models\ReviewLike::create([
                'review_id' => $review->id,
                'user_id' => $userId,
            ]);
            $review->increment('likes_count');
            $liked = true;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $review->fresh()->likes_count,
            ]);
        }

        return back();
    }
}
