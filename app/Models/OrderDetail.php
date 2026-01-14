<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'order_details';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'book_id',
        'quantity',
        'price',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that owns the order detail.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the book associated with this order detail.
     * Assuming you have a Book model
     */
    public function book()
    {
        return $this->belongsTo(Book::class,'book_id');
    }

    /**
     * Calculate the total price for this order detail.
     */
    public function getTotalPriceAttribute()
    {
        return $this->price * $this->quantity;
    }

    /**
     * Get formatted total price with currency.
     */
    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 2) . ' ر.س';
    }

    /**
     * Get formatted unit price with currency.
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ر.س';
    }

    /**
     * Scope to get order details for a specific order.
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope to get order details for a specific book.
     */
    public function scopeForBook($query, $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    /**
     * Get the subtotal for all items (static method for calculations).
     */
    public static function calculateSubtotal($orderDetails)
    {
        return $orderDetails->sum(function ($detail) {
            return $detail->price * $detail->quantity;
        });
    }
}