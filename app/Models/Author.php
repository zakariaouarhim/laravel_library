<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected function getSlugSource(): string
    {
        return (string) $this->name;
    }

    protected $fillable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'biography',
        'birth_date',
        'death_date',
        'nationality',
        'profile_image',
        'website',
        'api_source',
        'api_id',
        'api_last_updated',
        'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'api_last_updated' => 'datetime',
    ];

    // Many-to-many relationship with books through book_authors
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_authors')
                    ->withPivot('author_type')
                    ->withTimestamps();
    }

    // Books where this author is the primary author
    public function primaryBooks()
    {
        return $this->hasMany(Book::class, 'author_id');
    }

    // Get books by author type
    public function booksByType($type = 'primary')
    {
        return $this->belongsToMany(Book::class, 'book_authors')
                    ->wherePivot('author_type', $type)
                    ->withTimestamps();
    }

    // Series by this author
    public function series()
    {
        return $this->hasMany(Series::class);
    }

    // ---- Canonical-author grouping (same author in multiple scripts) ----
    // An "alias" row carries canonical_author_id pointing at the canonical row.
    // Links are always flat (alias -> root, never chains) — enforced in
    // AuthorController::linkCanonical().

    public function canonical()
    {
        return $this->belongsTo(Author::class, 'canonical_author_id');
    }

    public function aliases()
    {
        return $this->hasMany(Author::class, 'canonical_author_id');
    }

    public function isAlias(): bool
    {
        return $this->canonical_author_id !== null;
    }

    // Books whose author_id points at one of this root's alias rows.
    public function aliasBooks()
    {
        return $this->hasManyThrough(
            Book::class,
            Author::class,
            'canonical_author_id', // FK on authors (alias -> this root)
            'author_id',           // FK on books (book -> alias)
            'id',
            'id'
        );
    }

    public function scopeCanonicalOnly($query)
    {
        return $query->whereNull('canonical_author_id');
    }

    // The group's canonical row; degrades to $this when the canonical row is
    // soft-deleted/missing so alias pages can still render standalone.
    public function groupRoot(): Author
    {
        if ($this->canonical_author_id === null) {
            return $this;
        }

        return $this->canonical ?? $this;
    }

    // All author ids in this row's group (root + its aliases).
    public function groupIds()
    {
        $root = $this->groupRoot();

        return Author::where('id', $root->id)
            ->orWhere('canonical_author_id', $root->id)
            ->pluck('id');
    }

    // Every name the group's author is known by, root name first.
    public function allNames()
    {
        $root = $this->groupRoot();

        return $root->aliases->pluck('name')
            ->prepend($root->name)
            ->unique()
            ->values();
    }

    // Scope for active authors
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Accessor for full name (in case you want to add first/last name later)
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    // Check if author is alive
    public function getIsAliveAttribute()
    {
        return is_null($this->death_date);
    }

}
