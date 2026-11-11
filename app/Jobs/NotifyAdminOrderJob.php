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
        $order = Order::with(['orderDetails.book.publishingHouse', 'checkoutDetail'])->find($this->orderId);

        if (!$order) {
            return;
        }

        // Covers first (album for 2–10 books, single photo for one), then the
        // summary + action buttons as the last message so the buttons sit at
        // the bottom of the chat.
        $album = $presenter->albumItems($order);
        if (count($album) === 1) {
            $telegram->sendPhoto($album[0]['data'], $album[0]['caption']);
        } elseif (count($album) >= 2) {
            $telegram->sendMediaGroup($album);
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
