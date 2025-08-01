<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';
    protected $fillable = ['user_id']; // Add other fields if needed

    // Relationship with User (if applicable) : Defines a relationship between the Cart and User models.
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with CartItem : Defines a relationship between the Cart and CartItem models (a cart can have many items).
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
