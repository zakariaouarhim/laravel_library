<?php

namespace App\Listeners;

use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class MergeGuestCartOnLogin
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function handle(Login $event): void
    {
        try {
            $this->cartService->mergeGuestCartIntoDb($event->user->id);
        } catch (\Throwable $e) {
            Log::error('Guest cart merge failed on login', [
                'user_id' => $event->user->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
