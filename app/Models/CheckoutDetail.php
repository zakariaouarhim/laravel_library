<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutDetail extends Model
{
    use HasFactory;
    protected $fillable = [
          'order_id', 'first_name', 'last_name', 'email', 'phone', 'address', 
        'city', 'zip_code', 'payment_method', 'card_number', 
        'expiry_date', 'cvv', 'subtotal', 'shipping', 'discount', 
        'total', 'status', 'cart_items'
    ];

    protected $casts = [
        'cart_items' => 'array',
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationship with orders if needed
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    // Decrypt card details when accessed
    public function getCardNumberAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function getCvvAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }
}
