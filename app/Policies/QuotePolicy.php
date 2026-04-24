<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\UserModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    public function delete(UserModel $user, Quote $quote): bool
    {
        return $quote->user_id === $user->id;
    }
}
