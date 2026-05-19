<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Book;
use App\Models\Category;

class Series extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'author_id',
        'total_volumes',
        'cover_image',
        'is_complete',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
        'total_volumes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Series $series) {
            if (empty($series->slug)) {
                $base = trim($series->name);
                // Keep Arabic characters, letters, and numbers; replace everything else with hyphens
                $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', '-', $base);
                $slug = trim($slug, '-');
                if (empty($slug)) {
                    $slug = uniqid('series-');
                }
                // Ensure uniqueness (check soft-deleted records too)
                $original = $slug;
                $i = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $i++;
                }
                $series->slug = $slug;
            }
        });
    }

    public function getRouteKey()
    {
        return $this->slug ?: $this->getKey();
    }

    public function books()
    {
        return $this->hasMany(Book::class)->orderBy('volume_number');
    }

    public function bundle()
    {
        return $this->hasOne(Book::class)->where('product_type', 'bundle');
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function scopeComplete($query)
    {
        return $query->where('is_complete', true);
    }

    public function scopeOngoing($query)
    {
        return $query->where('is_complete', false);
    }

    // Series whose volumes include at least one book in the given language.
    // A mixed-language series will appear in both language-specific carousels,
    // which is acceptable since it is genuinely relevant to both audiences.
    public function scopeInLanguage($query, string $language)
    {
        return $query->whereHas('books', fn($q) => $q->where('language', $language));
    }

    // Series language is derived from its volumes (most common language wins).
    // Returns null when the series has no books or no languages set.
    public function getLanguageAttribute(): ?string
    {
        $books = $this->relationLoaded('books')
            ? $this->books
            : $this->books()->get(['id', 'series_id', 'language']);

        $languages = $books->pluck('language')->filter();
        if ($languages->isEmpty()) return null;

        return $languages->countBy()->sortDesc()->keys()->first();
    }

    public function getLanguageLabelAttribute(): ?string
    {
        $lang = $this->language;
        return $lang ? (Book::LANGUAGE_LABELS[$lang] ?? $lang) : null;
    }

    // Categories derived from the series' volumes — de-duplicated, ordered by
    // how often they appear across books (most common first). Returned as a
    // Collection, not a relation, since categories live on Book, not Series.
    public function derivedCategories()
    {
        return Category::query()
            ->whereIn('categories.id', function ($q) {
                $q->select('book_category.category_id')
                  ->from('book_category')
                  ->join('books', 'books.id', '=', 'book_category.book_id')
                  ->where('books.series_id', $this->id)
                  ->whereNull('books.deleted_at');
            })
            ->get();
    }
}
