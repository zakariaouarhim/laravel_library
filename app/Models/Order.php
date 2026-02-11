<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'total_price', 'shipping_address',
        'billing_address', 'payment_method', 'tracking_number',
        'customer_name',    // For guest orders
        'customer_email',   // For guest orders
        'customer_phone',   // For guest orders
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function checkoutDetail()
    {
        return $this->hasOne(CheckoutDetail::class);
    }
    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    // Relationship with User (nullable for guest orders)
    public function user()
    {
        return $this->belongsTo(UserModel::class,'user_id');
    }

    

    // Get customer name (from user or guest info)
    public function getCustomerNameAttribute()
    {
        return $this->user ? $this->user->name : $this->attributes['customer_name'];
    }

    // Get customer email (from user or guest info)
    public function getCustomerEmailAttribute()
    {
        return $this->user ? $this->user->email : $this->attributes['customer_email'];
    }

    // Check if this is a guest order
    public function isGuestOrder()
    {
        return is_null($this->user_id);
    }
}
