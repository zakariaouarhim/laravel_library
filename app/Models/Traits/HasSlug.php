<?php

namespace App\Models\Traits;

use App\Services\Seo\Slugger;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates and persists a unique `slug` column on entity creation.
 *
 * Slugs are stable URLs — they are NOT regenerated on updates. If admin
 * renames an entity, the original slug stays unless explicitly cleared.
 *
 * The host model must:
 *   1. have a `slug` column (string, unique, nullable)
 *   2. implement `getSlugSource(): string` returning the source text (title/name)
 *
 * Route-model binding via slug is enabled automatically via getRouteKeyName().
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = self::generateUniqueSlugFor($model);
            }
        });
    }

    abstract protected function getSlugSource(): string;

    protected static function generateUniqueSlugFor($model): string
    {
        $slugger = app(Slugger::class);
        $base = $slugger->make($model->getSlugSource());
        $slug = $base;
        $i = 2;
        // Raw DB query bypasses all model scopes (incl. SoftDeletes) so a
        // trashed row with the same slug is still detected. Otherwise restoring
        // it later would collide with the unique constraint.
        $table = $model->getTable();
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // Note: route-model binding by slug is opt-in per route via {book:slug}.
    // Don't override getRouteKeyName() globally — would break existing routes
    // that use implicit ID binding.

    /**
     * URL-generation key. When views do route('book.show', $book), Laravel
     * substitutes this value into the URL. Returning slug here means every
     * existing route() call automatically produces a slug URL without view edits.
     *
     * Routes that bind by ID (legacy /moredetail-v2/{id}) still work because
     * binding uses getRouteKeyName() (left at default 'id'), independent of this.
     */
    public function getRouteKey()
    {
        return $this->slug ?: $this->getKey();
    }
}
