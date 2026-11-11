<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;

/**
 * Builds the Telegram message + inline keyboard for an order.
 * Shared by NotifyAdminOrderJob (first send) and TelegramWebhookController
 * (re-render after a status change) so both always look identical.
 */
class OrderTelegramPresenter
{
    public function text(Order $order): string
    {
        $order->loadMissing(['orderDetails.book', 'checkoutDetail']);

        $detail  = $order->checkoutDetail;
        $name    = $detail?->full_name ?? $order->customer_name  ?? 'عميل';
        $phone   = $detail?->phone     ?? $order->customer_phone ?? '-';
        $address = $detail
            ? trim(($detail->address ?? '') . ', ' . ($detail->city ?? ''), ', ')
            : ($order->shipping_address ?? '-');
        $payment = $order->payment_label ?? $order->payment_method;
        $total   = number_format((float) $order->total_price, 2);

        $lines   = [];
        $lines[] = "🛒 <b>طلب</b> #{$order->id}";
        $lines[] = "📊 الحالة: <b>" . e($order->status->label()) . "</b>";
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

        return implode("\n", $lines);
    }

    /** One button per allowed next status, plus a dashboard link. */
    public function keyboard(Order $order): array
    {
        $rows = [];

        foreach ($order->status->allowedTransitions() as $st) {
            $rows[] = [[
                'text'          => $this->emoji($st) . ' ' . $st->label(),
                'callback_data' => "ord:{$order->id}:{$st->value}",
            ]];
        }

        $rows[] = [[
            'text' => '🔗 عرض في لوحة التحكم',
            'url'  => route('admin.orders.show', $order->id),
        ]];

        return ['inline_keyboard' => $rows];
    }

    /** Confirmation row shown before a destructive change is applied. */
    public function confirmKeyboard(Order $order, OrderStatus $target): array
    {
        return ['inline_keyboard' => [[
            ['text' => '✅ تأكيد: ' . $target->label(), 'callback_data' => "ord:{$order->id}:{$target->value}:y"],
            ['text' => '↩️ تراجع',                       'callback_data' => "ord:{$order->id}:back"],
        ]]];
    }

    /** Status changes that email the customer and are hard to undo → confirm first. */
    public function isDestructive(OrderStatus $status): bool
    {
        return in_array(
            $status,
            [OrderStatus::Cancelled, OrderStatus::Failed, OrderStatus::Refunded, OrderStatus::Returned],
            true
        );
    }

    private function emoji(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending    => '🕓',
            OrderStatus::Processing => '⚙️',
            OrderStatus::Shipped    => '🚚',
            OrderStatus::Delivered  => '✅',
            OrderStatus::Cancelled  => '❌',
            OrderStatus::Failed     => '⚠️',
            OrderStatus::Refunded   => '💸',
            OrderStatus::Returned   => '↩️',
        };
    }
}
