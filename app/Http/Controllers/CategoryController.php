<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;


class CategoryController extends Controller
{
    
    public function index()
    {
        $categorie = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->withCount(['books' => function ($q) {
                    $q->where('type', 'book');
                }]);
            }])
            ->withCount(['books' => function ($q) {
                $q->where('type', 'book');
            }])
            ->get();

        // Calculate total books (own + children) for each parent
        foreach ($categorie as $cat) {
            $cat->total_books = $cat->books_count + $cat->children->sum('books_count');
        }

        // Sort by total books descending
        $categorie = $categorie->sortByDesc('total_books')->values();

        $totalBooks = Book::where('type', 'book')->count();
        $totalCategories = Category::count();

        return view('categories', compact('categorie', 'totalBooks', 'totalCategories'));
    }
    
}
