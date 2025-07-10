<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserModel extends Authenticatable
{
    use HasFactory;
    
    protected $table = 'user';
    
    // Fixed the fillable fields - removed extra space and corrected 'Email' to 'email'
    protected $fillable = ['name', 'email', 'password', 'role'];
    
    // Hide password from JSON output
    protected $hidden = ['password', 'remember_token'];
    
    // Cast attributes
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Add this for debugging
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            \Log::info('Creating user with attributes:', $model->getAttributes());
        });
    }
    
    public function reviews() {
        return $this->hasMany(Book_Review::class, 'user_id');
    }
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
}