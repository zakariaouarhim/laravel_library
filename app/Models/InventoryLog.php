<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    /** The table has created_at only. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'book_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'notes'
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}