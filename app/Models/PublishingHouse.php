<?php

namespace App\Models;

use App\Models\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublishingHouse extends Model
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
        'address',
        'phone',
        'email',
        'website',
        'founded_year',
        'country',
        'description',
        'logo',
        'api_source',
        'api_id',
        'api_last_updated',
        'status'
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'api_last_updated' => 'datetime',
    ];

    // One-to-many relationship with books
    public function books()
    {
        return $this->hasMany(Book::class, 'publishing_house_id');
    }

    // Scope for active publishing houses
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope by country
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    // Get years in business
    public function getYearsInBusinessAttribute()
    {
        if (!$this->founded_year) {
            return null;
        }
        return now()->year - $this->founded_year;
    }

}
