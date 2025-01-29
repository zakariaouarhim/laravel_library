<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'book_id', 'quantity'];

    // Relationship with Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Relationship with Book
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
