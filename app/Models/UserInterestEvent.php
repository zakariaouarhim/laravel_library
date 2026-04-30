<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInterestEvent extends Model
{
    use HasFactory;

    protected $table = 'user_interest_events';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'rating_value',
    ];

    protected $casts = [
        'rating_value' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
