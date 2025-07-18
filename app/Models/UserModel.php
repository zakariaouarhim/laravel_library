<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserModel extends Authenticatable
{
    use HasFactory;
    
    protected $table = 'user';
    
    // Fixed the fillable fields - removed extra space and corrected 'Email' to 'email'
    protected $fillable = ['name', 'email', 'password', 'role','created_at','updated_at'];
    
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
    public function wishlist()
    {
        return $this->belongsToMany(
            Book::class,           // Related model
            'wishlists',           // Pivot table name
            'user_id',             // Foreign key on pivot table for current model
            'book_id',             // Foreign key on pivot table for related model
            'id',                  // Local key on current model
            'id'                   // Local key on related model
        )->withTimestamps();
    }

    // Alternative, more explicit way:
    public function wishlistBooks()
    {
        return $this->belongsToMany(Book::class, 'wishlists', 'user_id', 'book_id')
                    ->withTimestamps();
    }
}