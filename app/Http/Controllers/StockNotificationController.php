<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\StockNotification;
use Illuminate\Support\Facades\Auth;

class StockNotificationController extends Controller
{
    public function store($bookId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success'  => false,
                'redirect' => route('login2.page'),
            ], 401);
        }

        $book = Book::findOrFail($bookId);

        if (($book->Quantity ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'الكتاب متوفر حالياً، يمكنك إضافته للسلة مباشرة.',
            ], 422);
        }

        $existing = StockNotification::where('book_id', $bookId)
            ->where('user_id', Auth::id())
            ->whereNull('notified_at')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مسجل بالفعل للإشعار عند توفر هذا الكتاب.',
            ], 422);
        }

        StockNotification::updateOrCreate(
            ['book_id' => $bookId, 'user_id' => Auth::id()],
            ['email' => Auth::user()->email, 'notified_at' => null]
        );

        return response()->json([
            'success' => true,
            'message' => 'سنُعلمك فور توفر هذا الكتاب!',
        ]);
    }
}
