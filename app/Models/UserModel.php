<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    use HasFactory;
    protected $table = 'user';
    protected $fillable = ['name ', 'Email','password','role']; // Specify the columns that can be mass-assigned

    // Add this for debugging
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            \Log::info('Creating user with attributes:', $model->getAttributes());
        });
    }
}
