<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingShelf extends Model
{
    protected $fillable = ['user_id', 'book_id', 'status', 'started_at', 'finished_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    const STATUS_LABELS = [
        'want_to_read' => 'أريد قراءته',
        'reading'      => 'أقرأه حالياً',
        'read'         => 'قرأته',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
