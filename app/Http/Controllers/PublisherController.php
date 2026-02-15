<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookService;
use App\Services\APIService;

class PublisherController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->query('q');

        $publishers = PublishingHouse::where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'country')
            ->limit(10)
            ->get();

        return response()->json($publishers);
    }

    public function publicIndex(Request $request)
    {
        $query = PublishingHouse::active()->withCount(['books' => function ($q) {
            $q->where('type', 'book');
        }]);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }

        $sort = $request->input('sort', 'name');
        switch ($sort) {
            case 'books':
                $query->orderBy('books_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $publishers = $query->paginate(24);

        $countries = PublishingHouse::active()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country')
            ->sort();

        if ($request->ajax()) {
            return response()->json([
                'publishers' => $publishers->items(),
                'total' => $publishers->total(),
            ]);
        }

        return view('publishers', compact('publishers', 'countries'));
    }

    public function publicShow($id)
    {
        $publisher = PublishingHouse::active()->withCount(['books' => function ($q) {
            $q->where('type', 'book');
        }])->findOrFail($id);

        $books = $publisher->books()->where('type', 'book')->paginate(12);

        return view('publisher', compact('publisher', 'books'));
    }
}
