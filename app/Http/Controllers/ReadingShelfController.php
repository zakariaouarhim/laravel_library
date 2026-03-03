<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReadingShelf;
use Illuminate\Support\Facades\Auth;

class ReadingShelfController extends Controller
{
    public function store(Request $request, $bookId)
    {
        $request->validate([
            'status' => 'required|in:want_to_read,reading,read',
        ]);

        $status = $request->input('status');
        $now = now();

        $shelf = ReadingShelf::updateOrCreate(
            ['user_id' => Auth::id(), 'book_id' => $bookId],
            [
                'status'      => $status,
                'started_at'  => $status === 'reading' ? $now : null,
                'finished_at' => $status === 'read' ? $now : null,
            ]
        );

        // Preserve started_at when moving from reading → read
        if ($status === 'read' && !$shelf->started_at) {
            $shelf->started_at = $shelf->created_at;
            $shelf->save();
        }

        return response()->json([
            'success' => true,
            'status'  => $status,
            'label'   => ReadingShelf::STATUS_LABELS[$status],
        ]);
    }

    public function remove($bookId)
    {
        ReadingShelf::where('user_id', Auth::id())
            ->where('book_id', $bookId)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function index()
    {
        $userId = Auth::id();

        $shelves = ReadingShelf::where('user_id', $userId)
            ->with('book.primaryAuthor')
            ->get()
            ->groupBy('status');

        return response()->json([
            'want_to_read' => $shelves->get('want_to_read', collect())->values(),
            'reading'      => $shelves->get('reading', collect())->values(),
            'read'         => $shelves->get('read', collect())->values(),
        ]);
    }
}
