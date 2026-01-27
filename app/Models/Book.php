<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory, Searchable;
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
    protected $fillable = [
        'title',
        'author',           // Keep for backward compatibility
        'author_id',        // New primary author ID
        'description',
        'price',
        'category_id',
        'image',
        'Page_Num',
        'Langue',
        'Publishing_House', // Keep for backward compatibility
        'publishing_house_id',
        'ISBN',
        'Quantity',
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

    // Define relationships
    public function category()
    {
       return $this->belongsTo(Category::class, 'category_id');
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
        
        // Fallback to the old author field
        return $this->attributes['author'] ?? 'Unknown Author';
    }

    // Enhanced publishing house name accessor
    public function getPublishingHouseNameAttribute()
    {
        if ($this->publishingHouse) {
            return $this->publishingHouse->name;
        }
        
        // Fallback to the old Publishing_House field
        return $this->attributes['Publishing_House'] ?? 'Unknown Publisher';
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
        return $query->whereColumn('Quantity', '<=', 'min_stock_level');
    }

    // Scope for active books
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Check if book is in stock
    public function getInStockAttribute()
    {
        return $this->Quantity > 0 && $this->status === 'active';
    }

    // Check if book needs reordering
    public function getNeedsReorderAttribute()
    {
        return $this->Quantity <= $this->reorder_point;
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
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
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