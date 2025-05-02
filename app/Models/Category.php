<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'parent_id '
    ];
     // Parent category has many children
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    // Child category belongs to a parent
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    // Relationship with Books
    public function books()
    {
        return $this->hasMany(Book::class);
    }
    
}