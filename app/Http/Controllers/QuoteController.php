<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreQuoteRequest;
use App\Models\Quote;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    /**
     * Store a new quote
     */
    public function store(StoreQuoteRequest $request)
    {
        try {
            // Check if user already has a quote for this book (optional limit)
            $existingQuotesCount = Quote::where('user_id', Auth::id())
                                      ->where('book_id', $request->book_id)
                                      ->count();

            if ($existingQuotesCount >= 5) { // Limit 5 quotes per book per user
                return redirect()->back()
                               ->with('quote_error', 'لا يمكنك إضافة أكثر من 5 اقتباسات لنفس الكتاب');
            }

            $quote = Quote::create([
                'book_id' => $request->book_id,
                'user_id' => Auth::id(),
                'text' => trim($request->text),
                'is_approved' => true // Auto-approve, you can change this for moderation
            ]);

            return redirect()->back()
                           ->with('quote_success', 'تم إضافة الاقتباس بنجاح!')
                           ->withFragment('quotes-tab');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('quote_error', 'حدث خطأ أثناء إضافة الاقتباس. يرجى المحاولة مرة أخرى')
                           ->withInput();
        }
    }

    /**
     * Toggle like/unlike for a quote
     */
    public function toggleLike(Quote $quote)
    {
        if (!Auth::check()) {
            return redirect()->route('login2.page')
                           ->with('message', 'يرجى تسجيل الدخول للإعجاب بالاقتباسات');
        }

        try {
            $liked = $quote->toggleLike(Auth::user());
            
            $message = $liked ? 'تم الإعجاب بالاقتباس' : 'تم إلغاء الإعجاب بالاقتباس';
            
            // If it's an AJAX request, return JSON
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'liked' => $liked,
                    'likes_count' => $quote->fresh()->likes_count,
                    'message' => $message
                ]);
            }

            return redirect()->back()
                           ->with('quote_success', $message)
                           ->withFragment('quotes-tab');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء معالجة طلبك'
                ], 500);
            }

            return redirect()->back()
                           ->with('quote_error', 'حدث خطأ أثناء معالجة طلبك');
        }
    }

    /**
     * Delete a quote (only by owner or admin)
     */
    public function destroy(Quote $quote)
    {
        if (!Auth::check()) {
            return redirect()->route('login2.page');
        }

        // Check if user owns the quote or is admin
        if ($quote->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return redirect()->back()
                           ->with('quote_error', 'غير مسموح لك بحذف هذا الاقتباس');
        }

        try {
            $quote->delete();
            
            return redirect()->back()
                           ->with('quote_success', 'تم حذف الاقتباس بنجاح')
                           ->withFragment('quotes-tab');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('quote_error', 'حدث خطأ أثناء حذف الاقتباس');
        }
    }

    /**
     * Get user's quotes
     */
    public function getUserQuotes()
    {
        if (!Auth::check()) {
            return redirect()->route('login2.page');
        }

        $quotes = Quote::where('user_id', Auth::id())
                      ->with('book')
                      ->orderBy('created_at', 'desc')
                      ->paginate(15);

        return view('user.quotes', compact('quotes'));
    }
}