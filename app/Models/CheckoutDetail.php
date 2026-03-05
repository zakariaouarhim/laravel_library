<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'full_name', 'email', 'phone', 'address',
        'city', 'notes', 'payment_method', 'subtotal', 'shipping',
        'discount', 'total', 'status', 'cart_items'
    ];

    protected $casts = [
        'cart_items' => 'array',
        'subtotal'   => 'decimal:2',
        'shipping'   => 'decimal:2',
        'discount'   => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
