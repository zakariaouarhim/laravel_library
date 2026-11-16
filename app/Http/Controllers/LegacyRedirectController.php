<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\Series;

/**
 * 301 redirects from legacy numeric-id URLs to the slug URLs.
 * Controller methods (not route closures) so `route:cache` works —
 * closures make the whole route file uncacheable.
 */
class LegacyRedirectController extends Controller
{
    public function book($id)
    {
        $book = Book::findOrFail($id);
        return redirect()->route('moredetail2.page', $book, 301);
    }

    public function category($id)
    {
        $category = Category::findOrFail($id);
        return redirect()->route('by-category', $category, 301);
    }

    public function author($id)
    {
        $author = Author::findOrFail($id);
        // Alias ids go straight to the canonical page to avoid a chained 301.
        $root = $author->groupRoot();
        if ($root->id !== $author->id && $root->status === 'active') {
            $author = $root;
        }
        return redirect()->route('author.show', $author, 301);
    }

    public function publisher($id)
    {
        $publisher = PublishingHouse::findOrFail($id);
        return redirect()->route('publisher.show', $publisher, 301);
    }

    public function series($id)
    {
        $series = Series::findOrFail($id);
        return redirect()->route('series.show', $series, 301);
    }
}
