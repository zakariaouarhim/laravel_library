<?php

namespace App\Policies;

use App\Models\Book_Review;
use App\Models\UserModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function update(UserModel $user, Book_Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(UserModel $user, Book_Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
