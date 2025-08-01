<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    /**
     * Store a new quote
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
            'text' => 'required|string|min:10|max:1000',
        ], [
            'book_id.required' => 'معرف الكتاب مطلوب',
            'book_id.exists' => 'الكتاب غير موجود',
            'text.required' => 'نص الاقتباس مطلوب',
            'text.min' => 'الاقتباس يجب أن يكون على الأقل 10 أحرف',
            'text.max' => 'الاقتباس يجب أن لا يزيد عن 1000 حرف',
            'page_number.integer' => 'رقم الصفحة يجب أن يكون رقماً صحيحاً',
            'page_number.min' => 'رقم الصفحة يجب أن يكون أكبر من 0',
            'page_number.max' => 'رقم الصفحة يجب أن لا يزيد عن 9999'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput()
                           ->with('quote_error', 'يرجى تصحيح الأخطاء والمحاولة مرة أخرى');
        }

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
     * Get quotes for a specific book (API endpoint)
     */
    public function getBookQuotes(Book $book)
    {
        $quotes = $book->publicQuotes()
                      ->orderBy('created_at', 'desc')
                      ->paginate(10);

        return response()->json($quotes);
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