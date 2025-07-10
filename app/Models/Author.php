<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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
