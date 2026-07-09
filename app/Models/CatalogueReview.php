<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Review state (imported/skipped + resulting book) for a catalogue_reference row.
 * @see CatalogueReference
 */
class CatalogueReview extends Model
{
    protected $fillable = [
        'catalogue_reference_id',
        'status',
        'book_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
}
