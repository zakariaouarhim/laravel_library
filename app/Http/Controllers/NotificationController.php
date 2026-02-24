<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function unreadCount()
    {
        if (!Auth::check()) {
            return response()->json(['count' => 0]);
        }

        $count = UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function recent()
    {
        if (!Auth::check()) {
            return response()->json(['notifications' => []]);
        }

        $notifications = UserNotification::where('user_id', Auth::id())
            ->latest()
            ->take(10)
            ->get(['id', 'type', 'title', 'body', 'url', 'read_at', 'created_at']);

        return response()->json(['notifications' => $notifications]);
    }

    public function markRead($id)
    {
        $notification = UserNotification::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
