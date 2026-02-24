<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'url', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Create a "new book" notification for a user.
     */
    public static function newBook(int $userId, Book $book): self
    {
        return self::create([
            'user_id' => $userId,
            'type'    => 'new_book',
            'title'   => 'كتاب جديد متوفر',
            'body'    => 'أُضيف كتاب جديد: «' . $book->title . '»',
            'url'     => route('moredetail2.page', $book->id),
        ]);
    }
}
