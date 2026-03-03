<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Book_Review;
use App\Models\Category;

class RecommendationController extends Controller
{
    public function recommendations(Request $request)
    {
        $userId = auth()->id();

        $favoriteCategories = Book_Review::where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->with('book.category')
            ->get()
            ->pluck('book.category.id')
            ->filter()
            ->unique()
            ->values();

        $reviewedBookIds = Book_Review::where('user_id', $userId)
            ->pluck('book_id')
            ->toArray();

        $query = Book::query()->with(['category', 'primaryAuthor']);

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        } elseif ($favoriteCategories->isNotEmpty()) {
            $query->whereIn('category_id', $favoriteCategories);
        }

        $hideReviewed = $request->input('hide_reviewed', '1');
        if ($hideReviewed === '1' && !empty($reviewedBookIds)) {
            $query->whereNotIn('id', $reviewedBookIds);
        }

        if ($request->filled('language')) {
            $query->where('Langue', $request->input('language'));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'rating':
                $query->withAvg('reviews', 'rating')
                      ->orderByDesc('reviews_avg_rating');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $books = $query->paginate(12)->appends($request->query());

        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->get();

        $wishlistBookIds = auth()->user()->wishlist()->pluck('books.id')->toArray();

        return view('recommendations', compact(
            'books',
            'categories',
            'favoriteCategories',
            'wishlistBookIds',
            'hideReviewed'
        ));
    }
}
