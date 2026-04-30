<?php

namespace App\Models;

use App\Services\UserInterestService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book_Review extends Model
{
    use HasFactory;

    protected $table = 'reviews';
    
    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
        'is_read',
        'likes_count',
        'status',
    ];

    // Cast created_at and updated_at to Carbon instances
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id', 'id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'id');
    }

    public function likes()
    {
        return $this->hasMany(ReviewLike::class, 'review_id');
    }

    public function isLikedBy($user): bool
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    protected static function booted(): void
    {
        static::created(function (Book_Review $review) {
            if ($book = $review->book) {
                app(UserInterestService::class)->recordReview(
                    $review->user_id,
                    $book,
                    (int) $review->rating
                );
            }
        });

        static::updated(function (Book_Review $review) {
            if ($review->wasChanged('rating') && ($book = $review->book)) {
                app(UserInterestService::class)->recordReview(
                    $review->user_id,
                    $book,
                    (int) $review->rating
                );
            }
        });

        static::deleted(function (Book_Review $review) {
            app(UserInterestService::class)->revertReview($review->user_id, $review->book_id);
        });
    }
}