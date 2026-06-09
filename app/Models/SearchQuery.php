<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Logged user search query. Drives the admin search-insights dashboard:
 * - Top queries (popular vocabulary for SEO copy)
 * - Zero-result queries (inventory or wording gaps)
 * - Trend over time
 */
class SearchQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'query', 'normalized_query', 'result_count', 'source', 'user_id',
    ];

    protected $casts = [
        'result_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeSince($query, \DateTimeInterface $since)
    {
        return $query->where('created_at', '>=', $since);
    }

    public function scopeZeroResults($query)
    {
        return $query->where('result_count', 0);
    }
}
