<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\UserModel;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    public function view(?UserModel $user, Order $order): bool
    {
        return $user !== null
            && $order->user_id !== null
            && $order->user_id === $user->id;
    }

    public function cancel(UserModel $user, Order $order): bool
    {
        return $order->user_id === $user->id
            && $order->status->isCancellable();
    }
}
