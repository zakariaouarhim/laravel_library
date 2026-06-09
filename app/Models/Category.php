<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected function getSlugSource(): string
    {
        return (string) $this->name;
    }

    protected $fillable = [
        'name', 'slug', 'meta_title', 'meta_description', 'editorial_content',
        'parent_id', 'language', 'categorie_icon', 'categorie_image',
    ];

    /**
     * Render editorial_content (admin-provided plain text) as safe paragraphs.
     * Splits on blank lines and HTML-escapes each chunk before wrapping in <p>.
     */
    public function getEditorialContentHtmlAttribute(): string
    {
        if (empty($this->editorial_content)) {
            return '';
        }
        return collect(preg_split('/\R\s*\R/', trim($this->editorial_content)))
            ->filter()
            ->map(fn ($para) => '<p>' . e(trim($para)) . '</p>')
            ->implode("\n");
    }
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

    public function interestedUsers()
    {
        return $this->hasMany(UserCategoryInterest::class);
    }

    // Get all books including from child categories (via pivot)
    public function allBooks()
    {
        $allCategoryIds = array_merge([$this->id], $this->children->pluck('id')->toArray());

        return Book::whereHas('categories', fn($q) => $q->whereIn('book_category.category_id', $allCategoryIds));
    }
}