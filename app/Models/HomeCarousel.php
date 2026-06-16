<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCarousel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'source_type', 'language', 'author_id', 'book_limit', 'is_active', 'sort_order',
        'system_key', 'show_unavailable',
    ];

    protected $casts = [
        'book_limit'       => 'integer',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
        'show_unavailable' => 'boolean',
    ];

    /**
     * Registry of built-in carousels. Keyed by system_key; resolution logic lives
     * in HomeCarouselService. 'render' picks the homepage component; 'dom_id' keeps
     * the original wrapper id (some are linked from the header, e.g. #popular-books).
     * 'group' = global (cacheable) | personalized (per-user) | session.
     */
    public const SYSTEM = [
        'recommended'      => ['render' => 'books',      'dom_id' => 'recommended-for-you', 'group' => 'personalized'],
        'from_follows'     => ['render' => 'books',      'dom_id' => 'from-follows',        'group' => 'personalized'],
        'new_arrivals'     => ['render' => 'books',      'dom_id' => 'all-books',           'group' => 'global'],
        'categories_strip' => ['render' => 'categories', 'dom_id' => 'categories-strip',    'group' => 'global'],
        'popular'          => ['render' => 'books',      'dom_id' => 'popular-books',       'group' => 'global'],
        'arabic_series'    => ['render' => 'series',     'dom_id' => 'arabic-series',       'group' => 'global'],
        'accessories'      => ['render' => 'books',      'dom_id' => 'Accessories',         'group' => 'global'],
        'english_books'    => ['render' => 'books',      'dom_id' => 'english-books',       'group' => 'global'],
        'english_series'   => ['render' => 'series',     'dom_id' => 'english-series',      'group' => 'global'],
        'french_books'     => ['render' => 'books',      'dom_id' => 'french-books',        'group' => 'global'],
        'recently_viewed'  => ['render' => 'books',      'dom_id' => 'recently-viewed',     'group' => 'session'],
    ];

    public function getIsSystemAttribute(): bool
    {
        return $this->system_key !== null;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'home_carousel_category');
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'home_carousel_book');
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the books shown in this carousel based on its source, in random
     * order, capped at book_limit. Returns standard (non-bundle) books only.
     */
    public function resolveBooks()
    {
        $query = Book::query()
            ->where('type', 'book')
            ->standardOnly()
            ->with(['primaryAuthor', 'authors', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating');

        // "Only available" toggle — restrict to in-stock books.
        if (!$this->show_unavailable) {
            $query->where('quantity', '>', 0);
        }

        // Optional language filter (author/categories sources can span languages).
        if ($this->language) {
            $query->where('language', $this->language);
        }

        switch ($this->source_type) {
            case 'author':
                if (!$this->author_id) {
                    return collect();
                }
                $authorId = $this->author_id;
                $query->where(function ($q) use ($authorId) {
                    $q->where('author_id', $authorId)
                      ->orWhereHas('authors', fn($a) => $a->where('authors.id', $authorId));
                });
                break;

            case 'manual':
                $ids = $this->books()->pluck('books.id')->all();
                if (empty($ids)) {
                    return collect();
                }
                $query->whereIn('books.id', $ids);
                break;

            case 'categories':
            default:
                $categoryIds = $this->categories()->pluck('categories.id')->all();
                if (empty($categoryIds)) {
                    return collect();
                }
                $query->whereHas('categories', fn($c) => $c->whereIn('categories.id', $categoryIds));
                break;
        }

        return $query->inRandomOrder()
            ->limit($this->book_limit ?: 12)
            ->get();
    }
}
