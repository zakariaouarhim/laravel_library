<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\Order;
use App\Services\UserInterestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInterestScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $orderId) {}

    public function handle(UserInterestService $interests): void
    {
        $order = Order::with('orderDetails.book.categories')->find($this->orderId);

        if (!$order || !$order->user_id) {
            return; // guest order — skip
        }

        foreach ($order->orderDetails as $detail) {
            if ($detail->book) {
                $interests->recordPurchase($order->user_id, $detail->book);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('UpdateInterestScoresJob failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}
