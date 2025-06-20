<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory;
     // Define fillable attributes
    protected $fillable = [
        'title',
        'author',
        'description',
        'price',
        'category_id',
        'image',
        'Page_Num',
        'Langue',
        'Publishing_House',
        'ISBN',
        'Quantity',
        'api_data_status',
        'api_source',
        'api_id',
        'api_last_updated',
        'original_image',
        'local_image_path'
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
    // Define relationships (optional)
    public function category()
    {
       return $this->belongsTo(Category::class, 'category_id');
    }
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
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
}
