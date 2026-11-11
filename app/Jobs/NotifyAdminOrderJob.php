<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderTelegramPresenter;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAdminOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $orderId) {}

    public function handle(TelegramService $telegram, OrderTelegramPresenter $presenter): void
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->find($this->orderId);

        if (!$order) {
            return;
        }

        $telegram->sendMessage($presenter->text($order), null, $presenter->keyboard($order));
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('NotifyAdminOrderJob failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}
