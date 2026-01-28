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
                'message' => 'حدث خطأ أثناء الإضافة'
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

    // Handle guest wishlist in session
    public function addToSession($bookId)
    {
        try {
            $book = Book::find($bookId);
            if (!$book) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود'
                ], 404);
            }

            // Get wishlist from session or create new array
            $wishlist = session()->get('wishlist', []);

            // Check if already in wishlist
            if (in_array($bookId, $wishlist)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب موجود بالفعل في المفضلة'
                ], 409);
            }

            // Add to wishlist
            $wishlist[] = $bookId;
            session()->put('wishlist', $wishlist);

            return response()->json([
                'success' => true, 
                'message' => 'تم إضافة الكتاب للمفضلة',
                'book_id' => $bookId
            ]);

        } catch (\Exception $e) {
            Log::error('Session wishlist add error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء الإضافة'
            ], 500);
        }
    }

    // Remove from guest wishlist in session
    public function removeFromSession($bookId)
    {
        try {
            $book = Book::find($bookId);
            if (!$book) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود'
                ], 404);
            }

            // Get wishlist from session
            $wishlist = session()->get('wishlist', []);

            // Check if book is in wishlist
            if (!in_array($bookId, $wishlist)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الكتاب غير موجود في المفضلة'
                ], 404);
            }

            // Remove from wishlist
            $wishlist = array_filter($wishlist, function($id) use ($bookId) {
                return $id != $bookId;
            });

            session()->put('wishlist', array_values($wishlist));

            return response()->json([
                'success' => true, 
                'message' => 'تمت إزالة الكتاب من المفضلة'
            ]);

        } catch (\Exception $e) {
            Log::error('Session wishlist remove error: ' . $e->getMessage());
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
            Log::error('Hide recommendation error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء إخفاء الترشيح'
            ], 500);
        }
    }

    // Sync guest wishlist to database after login
    public function syncGuestWishlist()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'الرجاء تسجيل الدخول أولاً'
                ], 401);
            }

            // Get guest wishlist from session
            $guestWishlist = session()->get('wishlist', []);

            if (!empty($guestWishlist)) {
                // Filter out books that don't exist
                $validBooks = Book::whereIn('id', $guestWishlist)->pluck('id')->toArray();

                // Get current user wishlist
                $userWishlist = $user->wishlist()->pluck('book_id')->toArray();

                // Merge: add new books to user wishlist
                $newBooks = array_diff($validBooks, $userWishlist);
                
                if (!empty($newBooks)) {
                    $user->wishlist()->syncWithoutDetaching($newBooks);
                }

                // Clear session wishlist
                session()->forget('wishlist');

                return response()->json([
                    'success' => true, 
                    'message' => 'تم مزامنة المفضلة بنجاح',
                    'synced_count' => count($newBooks)
                ]);
            }

            return response()->json([
                'success' => true, 
                'message' => 'لا توجد عناصر للمزامنة',
                'synced_count' => 0
            ]);

        } catch (\Exception $e) {
            Log::error('Sync guest wishlist error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'حدث خطأ أثناء المزامنة'
            ], 500);
        }
    }
}