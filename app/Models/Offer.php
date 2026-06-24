<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'banner_image', 'type',
        'quantity', 'fixed_price', 'min_price', 'max_price', 'excluded_book_ids',
        'series_ids', 'bundle_ids',
        'starts_at', 'ends_at', 'is_active',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'fixed_price'       => 'decimal:2',
        'min_price'         => 'decimal:2',
        'max_price'         => 'decimal:2',
        'excluded_book_ids' => 'array',
        'series_ids'        => 'array',
        'bundle_ids'        => 'array',
        'quantity'          => 'integer',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'is_active'         => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            if (empty($offer->slug)) {
                $base = trim((string) $offer->title);
                // Keep Arabic characters, letters, and numbers; replace the rest with hyphens.
                $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', '-', $base);
                $slug = trim($slug, '-');
                if (empty($slug)) {
                    $slug = uniqid('offer-');
                }
                $original = $slug;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $i++;
                }
                $offer->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function books()
    {
        // Pivot table is offer_book (Laravel's alphabetical default would be book_offer).
        return $this->belongsToMany(Book::class, 'offer_book');
    }

    /** Whether this offer has a live price-range rule set. */
    public function hasPriceRule(): bool
    {
        return $this->min_price !== null || $this->max_price !== null;
    }

    /**
     * A single paginatable query for every book a customer may pick from:
     * hand-picked (offer_book) OR books matching the live price rule
     * (standard, active, within [min_price, max_price]). One books table, so
     * results are naturally deduped. This is the source of truth for eligibility.
     */
    public function eligibleBooksQuery()
    {
        $pickedIds = $this->books()->pluck('books.id')->all();
        $hasRule   = $this->hasPriceRule();
        $min       = $this->min_price;
        $max       = $this->max_price;

        return Book::query()
            ->where(function ($q) use ($pickedIds, $hasRule, $min, $max) {
                $matched = false;

                if (!empty($pickedIds)) {
                    $q->whereIn('id', $pickedIds);
                    $matched = true;
                }

                if ($hasRule) {
                    $q->orWhere(function ($r) use ($min, $max) {
                        $r->where('type', 'book')->standardOnly()->where('status', 'active');
                        if ($min !== null) $r->where('price', '>=', $min);
                        if ($max !== null) $r->where('price', '<=', $max);
                    });
                    $matched = true;
                }

                if (!$matched) {
                    $q->whereRaw('1 = 0'); // nothing picked and no rule => no eligible books
                }
            })
            ->when(!empty($this->excluded_book_ids), function ($q) {
                $q->whereNotIn('id', $this->excluded_book_ids); // admin-excluded books
            })
            ->when(!empty($this->unitMemberBookIds()), function ($q) {
                // Books shown as part of a series/bundle unit aren't also shown loose.
                $q->whereNotIn('id', $this->unitMemberBookIds());
            })
            ->with('primaryAuthor:id,name')
            ->orderBy('title');
    }

    /** The full loose eligible set (no units), e.g. for non-paginated needs. */
    public function resolveEligibleBooks()
    {
        return $this->eligibleBooksQuery()->get();
    }

    /** Eligible book ids for cart validation = loose books ∪ all unit member books. */
    public function eligibleBookIds(): array
    {
        $loose = $this->eligibleBooksQuery()->pluck('id')->all();

        return array_values(array_unique(array_merge($loose, $this->unitMemberBookIds())));
    }

    // ===================== Series / bundle units =====================

    protected ?array $resolvedUnitsCache = null;

    /**
     * Series/bundle units added to this offer, resolved to display data + member books.
     * @return array<int,array{type:string,id:int,label:string,image:?string,book_ids:int[],count:int}>
     */
    public function resolveUnits(): array
    {
        if ($this->resolvedUnitsCache !== null) {
            return $this->resolvedUnitsCache;
        }

        $units = [];

        if (!empty($this->series_ids)) {
            $series = Series::whereIn('id', $this->series_ids)
                ->with(['books' => fn($q) => $q->where('type', 'book')->standardOnly()])
                ->get();
            foreach ($series as $s) {
                $bookIds = $s->books->pluck('id')->all();
                if (empty($bookIds)) continue;
                $units[] = [
                    'type' => 'series', 'id' => $s->id, 'label' => $s->name,
                    'image' => $s->cover_image, 'book_ids' => $bookIds, 'count' => count($bookIds),
                ];
            }
        }

        if (!empty($this->bundle_ids)) {
            $bundles = Book::whereIn('id', $this->bundle_ids)->where('product_type', 'bundle')
                ->with(['items' => fn($q) => $q->where('type', 'book')->standardOnly()])
                ->get();
            foreach ($bundles as $b) {
                $bookIds = $b->items->pluck('id')->all();
                if (empty($bookIds)) continue;
                $units[] = [
                    'type' => 'bundle', 'id' => $b->id, 'label' => $b->title,
                    'image' => $b->image, 'book_ids' => $bookIds, 'count' => count($bookIds),
                ];
            }
        }

        return $this->resolvedUnitsCache = $units;
    }

    /** All member book ids across every unit. */
    public function unitMemberBookIds(): array
    {
        $ids = [];
        foreach ($this->resolveUnits() as $u) {
            $ids = array_merge($ids, $u['book_ids']);
        }

        return array_values(array_unique($ids));
    }

    /**
     * Offers that are active right now: flagged active and within any date window.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function getIsRunningAttribute(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }

    public function getHasEndedAttribute(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }
}
