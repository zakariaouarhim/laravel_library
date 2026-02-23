<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockNotification extends Model
{
    protected $fillable = ['book_id', 'user_id', 'email', 'notified_at'];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
