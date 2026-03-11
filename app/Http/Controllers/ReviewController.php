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
            'status' => 'pending',
        ]);

        if ($request->ajax()) {
            $review->load('user');

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال تقييمك بنجاح وسيظهر بعد المراجعة.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'is_read' => $review->is_read,
                    'status' => $review->status,
                    'created_at' => $review->created_at->diffForHumans(),
                    'user_name' => $review->user->name ?? 'مستخدم',
                    'user_initial' => mb_substr($review->user->name ?? 'م', 0, 1),
                ],
                'summary' => $this->getReviewSummary($request->book_id),
            ]);
        }

        return back()->with('success', 'تم إرسال تقييمك بنجاح وسيظهر بعد المراجعة.');
    }

    public function update(Request $request, Book_Review $review)
    {
        $this->authorizeReviewOwner($request, $review);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث تقييمك بنجاح.',
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                ],
                'summary' => $this->getReviewSummary($review->book_id),
            ]);
        }

        return back()->with('success', 'تم تحديث تقييمك بنجاح.');
    }

    public function destroy(Request $request, Book_Review $review)
    {
        $this->authorizeReviewOwner($request, $review);

        $bookId = $review->book_id;
        $review->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف تقييمك.',
                'summary' => $this->getReviewSummary($bookId),
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

    private function getReviewSummary(int $bookId): array
    {
        return [
            'avg_rating' => round(Book_Review::where('book_id', $bookId)->where('status', 'approved')->avg('rating') ?? 0, 1),
            'reviews_count' => Book_Review::where('book_id', $bookId)->where('status', 'approved')->count(),
        ];
    }

    private function authorizeReviewOwner(Request $request, Book_Review $review): void
    {
        if (auth()->id() !== $review->user_id) {
            if ($request->ajax()) {
                abort(response()->json(['success' => false, 'message' => 'غير مصرح'], 403));
            }
            abort(403);
        }
    }

    // ==================== ADMIN METHODS ====================

    public function adminIndex(Request $request)
    {
        $query = Book_Review::with(['user', 'book']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by book title or user name
        if ($request->filled('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('book', fn($b) => $b->where('title', 'like', "%{$search}%"));
            });
        }

        $reviews = $query->latest()->paginate(20)->appends($request->query());

        $statusCounts = Book_Review::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('Dashbord_Admin.reviews', compact('reviews', 'statusCounts'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,pending,rejected']);

        $review = Book_Review::findOrFail($id);
        $review->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة التقييم بنجاح',
            'status' => $review->status,
        ]);
    }

    public function adminDestroy($id)
    {
        $review = Book_Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التقييم بنجاح',
        ]);
    }
}
