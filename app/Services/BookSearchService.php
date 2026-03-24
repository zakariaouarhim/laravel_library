<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class BookSearchService
{
    /**
     * Full search: exact match → n-gram fallback.
     * Used by searchResults page and admin product search.
     *
     * @param  int|null $limit  null = no limit (returns Collection)
     */
    public function search(?string $query, ?int $limit = null)
    {
        if (!$query) {
            return collect();
        }

        $safeQuery = $this->sanitize($query);

        $builder = Book::where('title', 'LIKE', "%{$safeQuery}%")
            ->orWhereHas('primaryAuthor', fn($q) => $q->where('name', 'LIKE', "%{$safeQuery}%"))
            ->orWhere('isbn', $safeQuery)
            ->orWhereHas('publishingHouse', fn($q) => $q->where('name', 'LIKE', "%{$safeQuery}%"));

        $builder->with(['primaryAuthor', 'publishingHouse']);

        if ($limit) {
            $builder->take($limit);
        }

        $books = $builder->get();

        if ($books->isNotEmpty()) {
            return $books;
        }

        return $this->ngramFallback($safeQuery, $limit ?? 20);
    }

    /**
     * Return a query builder for search (supports DB-level pagination).
     * Does NOT execute — caller chains filters, sorting, and ->paginate().
     */
    public function searchQuery(?string $query): Builder
    {
        $builder = Book::query()->where('status', 'active');

        if (!$query) {
            return $builder;
        }

        $safeQuery = $this->sanitize($query);

        $builder->where(function ($q) use ($safeQuery) {
            $q->where('title', 'LIKE', "%{$safeQuery}%")
              ->orWhereHas('primaryAuthor', fn($sub) => $sub->where('name', 'LIKE', "%{$safeQuery}%"))
              ->orWhere('isbn', $safeQuery)
              ->orWhereHas('publishingHouse', fn($sub) => $sub->where('name', 'LIKE', "%{$safeQuery}%"));
        });

        return $builder;
    }

    /**
     * N-gram fallback as a query builder (for DB-level pagination).
     */
    public function ngramSearchQuery(string $query): Builder
    {
        $safeQuery = $this->sanitize($query);
        $tokens = [];

        for ($i = 0; $i < mb_strlen($safeQuery) - 2; $i++) {
            $tokens[] = mb_substr($safeQuery, $i, 3);
        }

        $tokens = array_slice($tokens, 0, 3);
        $builder = Book::query()->where('status', 'active');

        if (empty($tokens)) {
            return $builder->whereRaw('0 = 1'); // no results
        }

        $builder->where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                $q->orWhere('title', 'LIKE', "%{$token}%")
                  ->orWhereHas('primaryAuthor', fn($sub) => $sub->where('name', 'LIKE', "%{$token}%"))
                  ->orWhereHas('publishingHouse', fn($sub) => $sub->where('name', 'LIKE', "%{$token}%"));
            }
        });

        return $builder;
    }

    /**
     * Lightweight admin search (shipment / management) — specific columns, no fallback.
     */
    public function searchForAdmin(string $query, int $limit = 10)
    {
        $safeQuery = $this->sanitize($query);

        return Book::where('isbn', $query)
            ->orWhere('title', 'like', "%{$safeQuery}%")
            ->orWhereHas('primaryAuthor', fn($q) => $q->where('name', 'like', "%{$safeQuery}%"))
            ->select('id', 'isbn', 'title', 'author_id', 'price', 'quantity', 'cost_price')
            ->with('primaryAuthor')
            ->limit($limit)
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

    /**
     * N-gram fallback search for fuzzy matching (Arabic-friendly).
     */
    private function ngramFallback(string $query, int $limit)
    {
        $tokens = [];

        for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
            $tokens[] = mb_substr($query, $i, 3);
        }

        if (empty($tokens)) {
            return collect();
        }

        // Limit to first 3 trigrams to cap query complexity
        $tokens = array_slice($tokens, 0, 3);

        return Book::where(function ($q) use ($tokens) {
            foreach ($tokens as $token) {
                $q->orWhere('title', 'LIKE', "%{$token}%")
                  ->orWhereHas('primaryAuthor', fn($q) => $q->where('name', 'LIKE', "%{$token}%"))
                  ->orWhereHas('publishingHouse', fn($q) => $q->where('name', 'LIKE', "%{$token}%"));
            }
        })->with(['primaryAuthor', 'publishingHouse'])->take($limit)->get();
    }

    /**
     * Sanitize a search query for safe LIKE usage.
     */
    private function sanitize(string $query): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
    }
}
