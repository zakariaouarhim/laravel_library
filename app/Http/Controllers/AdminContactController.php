<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

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

        $totalCount = $stats->total_count;
        $unreadCount = $stats->unread_count;
        $todayCount = $stats->today_count;

        return view('Dashbord_Admin.ContactMessages', compact(
            'messages', 'totalCount', 'unreadCount', 'todayCount'
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

    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();

        return redirect()->route('admin.contact-messages.index')
            ->with('success', 'تم حذف الرسالة بنجاح');
    }
}
