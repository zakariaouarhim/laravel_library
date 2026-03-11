<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quote extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'user_id',
        'text',
        'is_approved',
        'likes_count'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'likes_count' => 'integer'
    ];

    /**
     * Get the book that owns the quote
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the user who created the quote
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class);
    }

    /**
     * The users that liked this quote
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'quote_likes', 'quote_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Check if a user has liked this quote
     */
    public function isLikedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Toggle like for a user
     */
    public function toggleLike($user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isLikedBy($user)) {
            $this->likes()->detach($user->id);
            $this->decrement('likes_count');
            return false; // unliked
        } else {
            $this->likes()->attach($user->id);
            $this->increment('likes_count');
            return true; // liked
        }
    }

}