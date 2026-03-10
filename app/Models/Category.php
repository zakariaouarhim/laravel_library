<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'parent_id', 'categorie_icon', 'categorie_image',
    ];
     // Parent category has many children
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }
    //has children
     public function scopeParentWithChildren($query)
    {
        return $query->whereHas('children');
    }
    //category images
    public function scopeWithImages($query)
    {
        return $query->whereNotNull('categorie_image');
    }
    //category icons
    public function scopeWithIcons($query)
    {
        return $query->whereNotNull('categorie_icon');
    }
    // Child category belongs to a parent
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    // Relationship with Books (many-to-many via pivot)
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_category')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }
    // Get all descendant categories (children, grandchildren, etc.)
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    // Get all books including from child categories (via pivot)
    public function allBooks()
    {
        $allCategoryIds = array_merge([$this->id], $this->children->pluck('id')->toArray());

        return Book::whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $allCategoryIds));
    }
}