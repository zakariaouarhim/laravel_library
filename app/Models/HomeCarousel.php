<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeCarousel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'source_type', 'author_id', 'book_limit', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'book_limit' => 'integer',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

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
