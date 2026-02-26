<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewLike extends Model
{
    protected $table = 'review_likes';

    protected $fillable = [
        'review_id',
        'user_id',
    ];

    public function review()
    {
        return $this->belongsTo(Book_Review::class, 'review_id');
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
