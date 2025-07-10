<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book_Review extends Model
{
    use HasFactory;
    
    protected $table = 'reviews';
    
    protected $fillable = [
        'user_id',  // Fixed: removed space
        'book_id',
        'rating',
        'comment',
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
}