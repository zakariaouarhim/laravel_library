<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminContactController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query()->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('filter') && $request->filter !== '') {
            if ($request->filter === 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter === 'read') {
                $query->where('is_read', true);
            }
        }

        // Search
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(15)->appends($request->query());

        $stats = ContactMessage::selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count
        ")->first();

        // Casts with a ?? 0 default: SUM(CASE ...) returns NULL, not 0, on an
        // empty table, which would render the cards blank rather than zero.
        $totalCount  = (int) ($stats->total_count ?? 0);
        $unreadCount = (int) ($stats->unread_count ?? 0);
        $todayCount  = (int) ($stats->today_count ?? 0);

        // AntiSpam increments this on every block but nothing read it until
        // now. The default matters: the key does not exist until the day's
        // first block, and Cache::get would otherwise hand the view null.
        //
        // The counter is NOT contact-only -- AntiSpam guards POST /contact and
        // POST /adduser and the key has no per-route component -- so the card
        // must be labelled as a site-wide figure. It is also a non-atomic
        // read-modify-write on the file store, so treat it as an indicator
        // rather than an audited number.
        $spamBlockedToday = (int) Cache::get('spam_blocked:' . now()->toDateString(), 0);

        return view('Dashbord_Admin.ContactMessages', compact(
            'messages', 'totalCount', 'unreadCount', 'todayCount', 'spamBlockedToday'
        ));
    }

    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);

        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json($message);
    }

    public function toggleRead($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->update(['is_read' => !$message->is_read]);

        return response()->json(['success' => true, 'is_read' => $message->is_read]);
    }

    /**
     * Delete many messages at once. Built because the inbox had accumulated
     * 9,443 rows of which 6 were genuine; the per-row trash icon was never
     * going to clear a future trickle.
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        // No `exists` rule on `ids.*` on purpose. It would fire one SELECT per
        // id (Validator batches `exists` only for an array attribute, not for
        // `.*` scalars), and worse, a row already removed in another tab would
        // 422 the WHOLE batch. A whereIn delete is naturally idempotent and
        // $deleted reports what actually happened.
        $deleted = ContactMessage::whereIn('id', $data['ids'])->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => "تم حذف {$deleted} رسالة بنجاح",
        ]);
    }

    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();

        return redirect()->route('admin.contact-messages.index')
            ->with('success', 'تم حذف الرسالة بنجاح');
    }
}
