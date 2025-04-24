<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Book extends Model
{
    use HasFactory,Searchable;
     // Define fillable attributes
    protected $fillable = [
        'title', 'author', 'price', 'image', 'category_id', 'description'
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
}
