<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;  
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class BookAuthor extends Pivot
{
    protected $table = 'book_authors';

    protected $fillable = [
        'book_id',
        'author_id',
        'author_type'
    ];

    public $timestamps = true;

    // Relationship to book
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // Relationship to author
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    // Scope by author type
    public function scopeByType($query, $type)
    {
        return $query->where('author_type', $type);
    }

    // Get primary authors
    public function scopePrimary($query)
    {
        return $query->where('author_type', 'primary');
    }

    // Get co-authors
    public function scopeCoAuthors($query)
    {
        return $query->where('author_type', 'co-author');
    }
}