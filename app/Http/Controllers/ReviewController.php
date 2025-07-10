<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book_Review;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // Check if user already reviewed this book
        $existingReview = Book_Review::where('user_id', auth()->id())
                                   ->where('book_id', $request->book_id)
                                   ->first();

        if ($existingReview) {
            return back()->with('error', 'لقد قمت بتقييم هذا الكتاب من قبل.');
        }

        Book_Review::create([
            'user_id' => auth()->id(),
            'book_id' => $request->book_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'تم إرسال تقييمك بنجاح.');
    }
}