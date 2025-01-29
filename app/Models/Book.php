<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;
     // Define fillable attributes
    protected $fillable = [
        'title', 'author', 'price', 'image', 'category_id', 'description'
    ];
    
     // Relationship with CartItem
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // Define relationships (optional)
    public function category()
    {
       return $this->belongsTo(Category::class, 'category_id');
    }
    
}
