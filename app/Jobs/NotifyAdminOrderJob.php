<?php

namespace App\Jobs;

use App\Models\Order;
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

    public function handle(TelegramService $telegram): void
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->find($this->orderId);

        if (!$order) {
            return;
        }

        $telegram->sendMessage($this->format($order));
    }

    private function format(Order $order): string
    {
        $detail  = $order->checkoutDetail;
        $name    = $detail?->full_name      ?? $order->customer_name  ?? 'عميل';
        $phone   = $detail?->phone          ?? $order->customer_phone ?? '-';
        $address = $detail
            ? trim(($detail->address ?? '') . ', ' . ($detail->city ?? ''), ', ')
            : ($order->shipping_address ?? '-');
        $payment = $order->payment_label ?? $order->payment_method;
        $total   = number_format((float) $order->total_price, 2);

        $lines   = [];
        $lines[] = "🛒 <b>طلب جديد</b> #{$order->id}";
        $lines[] = '';
        $lines[] = "👤 <b>" . e($name) . "</b>";
        $lines[] = "📞 " . e($phone);
        $lines[] = "📍 " . e($address);
        $lines[] = "💳 " . e($payment);
        $lines[] = '';
        $lines[] = '<b>الكتب:</b>';

        foreach ($order->orderDetails as $item) {
            $title = e($item->book?->title ?? '?');
            $qty   = (int) $item->quantity;
            $sub   = number_format((float) $item->price * $qty, 2);
            $lines[] = "• {$title} × {$qty} — {$sub} د.م";
        }

        $lines[] = '';
        $lines[] = "💰 <b>الإجمالي: {$total} د.م</b>";
        $lines[] = '';
        $lines[] = '<a href="' . route('admin.orders.show', $order->id) . '">عرض في لوحة التحكم</a>';

        return implode("\n", $lines);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('NotifyAdminOrderJob failed', [
            'order_id' => $this->orderId,
            'error'    => $e->getMessage(),
        ]);
    }
}
