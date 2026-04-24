<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

class AdminQuoteController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $visibility = $request->input('visibility');

        $query = Quote::with(['user:id,name,email', 'book:id,title,image']);

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('text', 'like', $like)
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', $like))
                  ->orWhereHas('book', fn($b) => $b->where('title', 'like', $like));
            });
        }

        if ($visibility === 'visible') {
            $query->where('is_approved', true);
        } elseif ($visibility === 'hidden') {
            $query->where('is_approved', false);
        }

        $quotes = $query->latest()->paginate(15)->appends($request->query());

        $stats = Quote::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as visible_count,
            SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as hidden_count,
            COUNT(DISTINCT book_id) as books_with_quotes
        ")->first();

        return view('Dashbord_Admin.quotes', [
            'quotes'           => $quotes,
            'totalQuotes'      => (int) $stats->total,
            'visibleQuotes'    => (int) $stats->visible_count,
            'hiddenQuotes'     => (int) $stats->hidden_count,
            'booksWithQuotes'  => (int) $stats->books_with_quotes,
            'search'           => $search,
            'visibilityFilter' => $visibility,
        ]);
    }

    public function show($id)
    {
        $quote = Quote::with(['user:id,name,email', 'book:id,title,image'])
            ->findOrFail($id);

        return response()->json($quote);
    }

    public function toggleApproval($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->is_approved = !$quote->is_approved;
        $quote->save();

        return response()->json([
            'success'     => true,
            'is_approved' => $quote->is_approved,
            'message'     => $quote->is_approved
                ? 'تم إظهار الاقتباس للجمهور.'
                : 'تم إخفاء الاقتباس عن الجمهور.',
        ]);
    }

    public function destroy($id)
    {
        $quote = Quote::findOrFail($id);
        $quote->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاقتباس بنجاح.',
        ]);
    }
}
