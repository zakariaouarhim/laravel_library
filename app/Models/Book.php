<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory, HasSlug, Searchable, SoftDeletes;

    protected function getSlugSource(): string
    {
        return (string) $this->title;
    }

     protected $table = 'books';
     const LANGUAGES = ['arabic', 'english', 'french', 'spanish', 'german'];
     const LANGUAGE_LABELS = [
        'arabic'  => 'العربية',
        'english' => 'الإنجليزية',
        'french'  => 'الفرنسية',
        'spanish' => 'الإسبانية',
        'german'  => 'الألمانية',
    ];
    // Define fillable attributes
    protected $appends = ['author_name', 'publishing_house_name'];

    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'type',
        'product_type',
        'author_id',
        'description',
        'price',
        'discount',
        'category_id',
        'image',
        'page_num',
        'language',
        'publishing_house_id',
        'series_id',
        'volume_number',
        'isbn',
        'quantity',
        'api_data_status',
        'api_source',
        'api_id',
        'api_last_updated',
        'api_error_message',
        'original_image',
        'local_image_path',
        'cost_price',
        'profit_margin',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'status'
    ];

    protected $casts = [
        'api_last_updated' => 'datetime',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit_margin' => 'decimal:2'
    ];

    // Relationship with CartItem
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getImageUrlAttribute()
    {
        return asset($this->image);
    }

    public function getThumbnailAttribute()
    {
        if ($this->image) {
            // Check exact match first
            $thumbPath = str_replace('images/books/', 'images/books/thumbs/', $this->image);
            if (file_exists(public_path($thumbPath))) {
                return $thumbPath;
            }
            // Check .webp variant (thumbnails are always saved as webp)
            $webpThumb = preg_replace('/\.\w+$/', '.webp', $thumbPath);
            if ($webpThumb !== $thumbPath && file_exists(public_path($webpThumb))) {
                return $webpThumb;
            }
        }
        return $this->image ?? 'images/book-placeholder.png';
    }

    /**
     * 800px-wide WebP variant used as the 2x source on the book detail page's
     * LCP <img srcset>. Falls back to the 400px image when the large variant
     * doesn't exist on disk (legacy books processed before the large/ pipeline).
     */
    public function getLargeImageAttribute()
    {
        if ($this->image) {
            $largePath = str_replace('images/books/', 'images/books/large/', $this->image);
            if (file_exists(public_path($largePath))) {
                return $largePath;
            }
        }
        return $this->image ?? 'images/book-placeholder.png';
    }

    // Primary category (denormalized for breadcrumbs/display)
    public function category()
    {
       return $this->belongsTo(Category::class, 'category_id');
    }

    // All categories (many-to-many via pivot)
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'book_category')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    // Sync categories and keep books.category_id in sync with primary
    public function syncCategories(array $categoryIds, int $primaryCategoryId): void
    {
        $pivotData = [];
        foreach ($categoryIds as $catId) {
            $pivotData[$catId] = ['is_primary' => ($catId == $primaryCategoryId)];
        }
        $this->categories()->sync($pivotData);
        $this->update(['category_id' => $primaryCategoryId]);
    }
    public function getIsNewAttribute()
    {
        return $this->created_at
            ? $this->created_at->gt(now()->subDays(30))
            : false;
    }
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->getAuthorNameAttribute(),
            'description' => $this->description,
        ];
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function shipmentItems()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function scopeNeedsEnrichment($query)
    {
        return $query->where('api_data_status', 'pending');
    }

    public function scopeEnriched($query)
    {
        return $query->where('api_data_status', 'enriched');
    }

    // Primary author relationship (one-to-many)
    public function primaryAuthor()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    // Many-to-many relationship with all authors
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors')
                    ->withPivot('author_type')
                    ->withTimestamps();
    }

    // Relationship to publishing house
    public function publishingHouse()
    {
        return $this->belongsTo(PublishingHouse::class, 'publishing_house_id');
    }

    // Relationship to series
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function isPartOfSeries(): bool
    {
        return !is_null($this->series_id);
    }

    // Bundle: books included in this bundle (only meaningful when product_type = 'bundle')
    public function items()
    {
        return $this->belongsToMany(Book::class, 'bundle_items', 'bundle_id', 'book_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    // Bundle: bundles that include this (standard) book
    public function bundles()
    {
        return $this->belongsToMany(Book::class, 'bundle_items', 'book_id', 'bundle_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function isBundle(): bool
    {
        return $this->product_type === 'bundle';
    }

    public function isStandard(): bool
    {
        return $this->product_type !== 'bundle';
    }

    // A standard volume is sold individually unless it's part of at least one bundle.
    public function isOnlySoldInBundle(): bool
    {
        if ($this->isBundle()) return false;
        if ($this->relationLoaded('bundles')) {
            return $this->bundles->isNotEmpty();
        }
        return $this->bundles()->exists();
    }

    // Sum of the member volumes' individual prices (used for "you save X").
    public function getItemsTotalPriceAttribute(): float
    {
        if (!$this->isBundle() || !$this->relationLoaded('items')) return 0;
        return (float) $this->items->sum(function ($item) {
            $qty = $item->pivot->quantity ?? 1;
            return ((float) $item->price) * $qty;
        });
    }

    public function getBundleSavingsAttribute(): float
    {
        if (!$this->isBundle()) return 0;
        $total = $this->items_total_price;
        $diff = $total - (float) $this->price;
        return $diff > 0 ? $diff : 0;
    }

    // Exclude bundles from listings by default; opt-in via ->withBundles() or ->onlyBundles().
    public function scopeStandardOnly($query)
    {
        return $query->where('product_type', 'standard');
    }

    public function scopeOnlyBundles($query)
    {
        return $query->where('product_type', 'bundle');
    }

    // Get authors by type
    public function authorsByType($type = 'primary')
    {
        return $this->belongsToMany(Author::class, 'book_authors')
                    ->wherePivot('author_type', $type);
    }

    // Get all co-authors (excluding primary)
    public function coAuthors()
    {
        return $this->authorsByType('co-author');
    }

    // Get editors
    public function editors()
    {
        return $this->authorsByType('editor');
    }

    // Get translators
    public function translators()
    {
        return $this->authorsByType('translator');
    }

    // Get illustrators
    public function illustrators()
    {
        return $this->authorsByType('illustrator');
    }

    public function getAuthorNameAttribute()
    {
        // Fast path: relationship already eager-loaded (zero queries)
        if ($this->relationLoaded('primaryAuthor')) {
            return $this->primaryAuthor?->name ?? 'Unknown Author';
        }

        // Fallback: lazy-load (for toSearchableArray, enrichment service)
        if ($this->primaryAuthor) {
            return $this->primaryAuthor->name;
        }

        $primaryAuthor = $this->authors()->wherePivot('author_type', 'primary')->first();
        return $primaryAuthor?->name ?? 'Unknown Author';
    }

    public function getPublishingHouseNameAttribute()
    {
        if ($this->relationLoaded('publishingHouse')) {
            return $this->publishingHouse?->name ?? 'Unknown Publisher';
        }
        return $this->publishingHouse?->name ?? 'Unknown Publisher';
    }

    // Scope for books with low stock
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_stock_level');
    }

    // Scope for active books
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope for books only
    public function scopeBooks($query)
    {
        return $query->where('type', 'book');
    }

    // Scope for accessories only
    public function scopeAccessories($query)
    {
        return $query->where('type', 'accessory');
    }

    // Check if book is in stock
    public function getInStockAttribute()
    {
        return $this->quantity > 0 && $this->status === 'active';
    }

    // Check if book needs reordering
    public function getNeedsReorderAttribute()
    {
        return $this->quantity <= $this->reorder_point;
    }
    public function reviews()
    {
        return $this->hasMany(Book_Review::class, 'book_id', 'id');
    }

    public function reviewsWithUsers()
    {
        return $this->hasMany(Book_Review::class, 'book_id', 'id')->with('user')->latest();
    }

    public function getAverageRatingAttribute()
    {
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return $this->attributes['reviews_avg_rating'] ?? 0;
        }
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewsCountAttribute()
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }
        return $this->reviews()->count();
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(UserModel::class, 'wishlists')->withTimestamps();
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class)
                    ->where('is_approved', true);
    }

    public function quotesWithUsers()
    {
        return $this->hasMany(Quote::class)
                    ->with('user', 'likes')
                    ->where('is_approved', true);
    }
}