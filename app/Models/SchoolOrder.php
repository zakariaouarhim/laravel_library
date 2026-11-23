<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A back-to-school pickup order taken at the counter.
 * The auto-increment id is the order number written on the customer's bag.
 */
class SchoolOrder extends Model
{
    protected $table = 'school_orders';

    protected $fillable = ['customer_name', 'phone', 'shelf', 'status', 'collected_at'];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Strip everything but digits so "06 61-23-45" and "0661" match each other,
     * both when saving and when searching.
     */
    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }
}
