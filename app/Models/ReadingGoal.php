<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingGoal extends Model
{
    use HasFactory;

     protected $fillable = ['user_id', 'target', 'year'];

    public function user()
    {
        return $this->belongsTo(Usermodel::class, 'user_id');
    }

}
