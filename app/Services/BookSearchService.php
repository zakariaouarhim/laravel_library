<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class BookSearchService
{
    /**
     * Cap on Scout-returned IDs for paginated search. Past this depth, ranking
     * is lost and results fall back to the DB's natural ordering.
     */
    private const SEARCH_HIT_CAP = 500;

    /**
     * Full-text search via Scout/Meilisearch with eager-loaded relations.
     */
    public function search(?string $query, ?int $limit = null)
    {
        if (!$query) {
            return collect();
        }

        $builder = Book::search($query)
            ->query(fn($qb) => $qb->with(['primaryAuthor', 'publishingHouse']));

        return $limit ? $builder->take($limit)->get() : $builder->get();
    }

    /**
     * Eloquent builder for paginated public search results.
     *
     * Two-stage: ask Meilisearch for matching IDs (capped to SEARCH_HIT_CAP),
     * then return an Eloquent builder constrained to those IDs. Caller is
     * responsible for sorting (controllers chain orderBy* per the user's
     * sort dropdown). Returning an Eloquent builder lets the caller chain
     * whereHas / whereIn / orderBy, which Scout's builder can't do for the
     * Meilisearch driver.
     */
    public function searchQuery(?string $query): Builder
    {
        $base = Book::query()->where('status', 'active');

        if (!$query) {
            return $base;
        }

        $matchedIds = Book::search($query)->take(self::SEARCH_HIT_CAP)->keys();

        if ($matchedIds->isEmpty()) {
            return $base->whereRaw('0 = 1');
        }

        return $base->whereIn('id', $matchedIds);
    }

    /**
     * Lightweight admin search. ISBN exact-match goes to the DB index;
     * anything else goes through Meilisearch.
     */
    public function searchForAdmin(string $query, int $limit = 10)
    {
        $isbnHit = Book::where('isbn', $query)
            ->select('id', 'isbn', 'title', 'author_id', 'price', 'quantity', 'cost_price')
            ->with('primaryAuthor')
            ->limit(1)
            ->get();

        if ($isbnHit->isNotEmpty()) {
            return $isbnHit;
        }

        return Book::search($query)
            ->take($limit)
            ->query(fn($qb) => $qb
                ->select('id', 'isbn', 'title', 'author_id', 'price', 'quantity', 'cost_price')
                ->with('primaryAuthor'))
            ->get();
    }

    /**
     * Get related books based on the categories of the given book collection.
     */
    public function getRelatedBooks($books, int $limit = 10)
    {
        if ($books->isEmpty()) {
            return collect();
        }

        $mainBook    = $books->first();
        $excludedIds = $books->pluck('id')->toArray();
        $catIds      = $mainBook->categories->pluck('id')->toArray();

        return Book::whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $catIds ?: [$mainBook->category_id]))
            ->whereNotIn('id', $excludedIds)
            ->with(['primaryAuthor', 'publishingHouse'])
            ->inRandomOrder()
            ->take($limit)
            ->get();
    }

    /**
     * Get sibling or child categories relative to the given category.
     */
    public function relatedCategories(int $categoryId, int $limit = 10)
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return collect();
        }

        if ($category->parent_id) {
            return Category::where('parent_id', $category->parent_id)
                ->where('id', '!=', $category->id)
                ->take($limit)
                ->get();
        }

        return Category::where('parent_id', $category->id)
            ->take($limit)
            ->get();
    }

    /**
     * Get the most popular categories by book count.
     */
    public function popularCategories(int $limit = 10)
    {
        return Category::withCount('books')
            ->orderByDesc('books_count')
            ->take($limit)
            ->get();
    }
}
