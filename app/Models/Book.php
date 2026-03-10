<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Illuminate\Support\Carbon;

class Book extends Model
{
    use HasFactory, Searchable, SoftDeletes;
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
        'type',
        'author_id',
        'description',
        'price',
        'discount',
        'category_id',
        'image',
        'page_num',
        'language',
        'publishing_house_id',
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
            'author' => $this->getAuthorNameAttribute(), // Use the accessor
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

    // Enhanced author name accessor
    public function getAuthorNameAttribute()
    {
        // First try to get from primary author relationship
        if ($this->primaryAuthor) {
            return $this->primaryAuthor->name;
        }
        
        // Then try to get primary author from many-to-many relationship
        $primaryAuthor = $this->authors()->wherePivot('author_type', 'primary')->first();
        if ($primaryAuthor) {
            return $primaryAuthor->name;
        }
        
        return 'Unknown Author';
    }

    // Enhanced publishing house name accessor
    public function getPublishingHouseNameAttribute()
    {
        if ($this->publishingHouse) {
            return $this->publishingHouse->name;
        }
        
        return 'Unknown Publisher';
    }

    // Get all authors as a formatted string
    public function getAllAuthorsAttribute()
    {
        $authors = collect();
        
        // Add primary author
        if ($this->primaryAuthor) {
            $authors->push($this->primaryAuthor->name);
        }
        
        // Add other authors from many-to-many
        $otherAuthors = $this->authors()->wherePivot('author_type', '!=', 'primary')->get();
        foreach ($otherAuthors as $author) {
            $type = $author->pivot->author_type;
            $authors->push($author->name . " ({$type})");
        }
        
        return $authors->join(', ');
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
    public function reviews() {
        return $this->hasMany(Book_Review::class, 'book_id', 'id')->with('user')->latest();
    }
    // Calculate average rating
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    // Get total reviews count
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }
    public function wishlistedBy()
    {
        return $this->belongsToMany(UserModel::class, 'wishlists')->withTimestamps();
    }
    /**
     * Get all quotes for this book
     */
    public function quotes()
    {
        return $this->hasMany(Quote::class)
                    ->with('user', 'likes')
                    ->where('is_approved', true);
    }

    /**
     * Get quotes count for this book
     */
    public function getQuotesCountAttribute(): int
    {
        return $this->quotes()->count();
    }
}