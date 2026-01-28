<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Book;

class WishlistController extends Controller
{
    public function add($bookId)
    {
        try {
            // Check if user is authenticated
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الرجاء تسجيل الدخول أولاً'
                ], 401);
            }
            
            // Validate book exists
            $book = Book::find($bookId);
            if (!$book) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود'
                ], 404);
            }

            // Check if book is already in wishlist
            $existsInWishlist = $user->wishlist()->where('book_id', $bookId)->exists();
            if ($existsInWishlist) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب موجود بالفعل في المفضلة'
                ], 409);
            }

            // Add to wishlist
            $result = $user->wishlist()->syncWithoutDetaching([$bookId]);
            return response()->json([
                'success' => true, 
                'message' => 'تم إضافة الكتاب للمفضلة',
                'book_id' => $bookId
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist add error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء الإضافة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function remove($bookId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الرجاء تسجيل الدخول أولاً'
                ], 401);
            }

            // Validate book exists
            $book = Book::find($bookId);
            if (!$book) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود'
                ], 404);
            }

            // Check if book is in wishlist before removing
            $existsInWishlist = $user->wishlist()->where('book_id', $bookId)->exists();
            if (!$existsInWishlist) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود في المفضلة'
                ], 404);
            }

            $user->wishlist()->detach($bookId);

            return response()->json([
                'success' => true, 
                'message' => 'تمت إزالة الكتاب من المفضلة'
            ]);

        } catch (\Exception $e) {
            Log::error('Wishlist remove error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء الإزالة'
            ], 500);
        }
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $wishlist = $user->wishlist()->with(['author', 'category'])->get();
            
            return view('wishlist.index', compact('wishlist'));
        } catch (\Exception $e) {
            Log::error('Wishlist index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ في تحميل المفضلة');
        }
    }

    public function hideRecommendation($bookId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الرجاء تسجيل الدخول أولاً'
                ], 401);
            }

            Log::info('Recommendation hidden', [
                'user_id' => $user->id,
                'book_id' => $bookId
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'تم إخفاء الترشيح'
            ]);

        } catch (\Exception $e) {
            Log::error('Hide recommendation error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'book_id' => $bookId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء إخفاء الترشيح'
            ], 500);
        }
    }
}