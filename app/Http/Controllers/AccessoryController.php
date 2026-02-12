<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class AccessoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::accessories();

        // Category filter
        if ($request->filled('category')) {
            $category = Category::find($request->category);
            if ($category) {
                $childIds = $category->children->pluck('id')->toArray();
                $allIds = array_merge([$category->id], $childIds);
                $query->whereIn('category_id', $allIds);
            }
        }

        // Price range filters
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Sorting
        switch ($request->input('sort')) {
            case 'newest':
                $query->latest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->latest();
                break;
        }

        $accessories = $query->paginate(12)->appends($request->query());

        // Get categories that have accessories
        $categories = Category::whereHas('books', function ($q) {
            $q->where('type', 'accessory');
        })->get();

        return view('accessories', compact('accessories', 'categories'));
    }
}
