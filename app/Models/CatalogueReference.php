<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only reference catalogue (~81k books scraped from almouggar.com) used to
 * enrich our own books when a field is missing. NOT customer-facing; populated
 * once from a mysqldump via `php artisan catalogue:import`.
 *
 * @property int|null    $scraped_book_id
 * @property string|null $isbn
 * @property string|null $title
 * @property string|null $title_normalized
 * @property string|null $author
 * @property string|null $author_normalized
 * @property string|null $description
 * @property string|null $price
 * @property string|null $cover_url
 * @property string|null $category
 * @property string|null $edition   Actually holds the publisher.
 * @property string|null $langue
 * @property string|null $pages
 * @property int         $completeness
 */
class CatalogueReference extends Model
{
    protected $table = 'catalogue_reference';

    /** Reference data is never written through the app. */
    public $timestamps = false;

    protected $guarded = [];
}
