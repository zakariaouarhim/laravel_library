<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount',
        'max_uses', 'used_count', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
        'value'      => 'float',
        'min_order_amount' => 'float',
    ];

    /**
     * Check whether this coupon is currently usable for a given subtotal.
     * Returns true/false. Errors are surfaced by the controller.
     */
    public function isValidFor(float $subtotal): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        if ($subtotal < $this->min_order_amount) return false;
        return true;
    }

    /**
     * Calculate how much discount this coupon gives on the given subtotal.
     */
    public function discountFor(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            $discount = $subtotal * ($this->value / 100);
        } else {
            $discount = $this->value;
        }
        // Discount cannot exceed the subtotal
        return min(round($discount, 2), $subtotal);
    }
}
