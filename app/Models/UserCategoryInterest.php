<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCategoryInterest extends Model
{
    use HasFactory;

    protected $table = 'user_category_interests';

    protected $fillable = [
        'user_id',
        'category_id',
        'score',
        'last_interaction_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'last_interaction_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
