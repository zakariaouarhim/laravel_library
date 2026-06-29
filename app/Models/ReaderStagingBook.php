<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A scraped book awaiting admin review (approve -> real Book, or skip).
 * Not part of the public catalogue; only used by the reader-import tool.
 */
class ReaderStagingBook extends Model
{
    protected $table = 'reader_staging_books';

    protected $fillable = [
        'external_id', 'name', 'author', 'isbn', 'page_num', 'publisher',
        'language', 'price', 'description', 'local_image', 'image_exists',
        'custom_image', 'description_rewritten', 'original_description',
        'source_categories', 'suggested_category_id', 'category_ids',
        'primary_category_id', 'stock', 'status', 'book_id', 'reviewed_at',
    ];

    protected $casts = [
        'source_categories'     => 'array',
        'category_ids'          => 'array',
        'image_exists'          => 'boolean',
        'description_rewritten' => 'boolean',
        'price'                 => 'decimal:2',
        'reviewed_at'           => 'datetime',
    ];
}
