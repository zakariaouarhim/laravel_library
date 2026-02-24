<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = ['user_id', 'followable_id', 'followable_type'];

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Check if a user follows a given entity.
     */
    public static function isFollowing(int $userId, string $type, int $entityId): bool
    {
        return self::where('user_id', $userId)
            ->where('followable_type', $type)
            ->where('followable_id', $entityId)
            ->exists();
    }

    /**
     * Get all user_ids following a given entity.
     */
    public static function followersOf(string $type, int $entityId): \Illuminate\Support\Collection
    {
        return self::where('followable_type', $type)
            ->where('followable_id', $entityId)
            ->pluck('user_id');
    }
}
