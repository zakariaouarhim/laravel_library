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
        $order->loadMissing(['orderDetails.book.publishingHouse', 'checkoutDetail']);

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
            $pub   = $item->book?->publishingHouse?->name;
            $qty   = (int) $item->quantity;
            $sub   = number_format((float) $item->price * $qty, 2);
            $pubTxt = $pub ? ' — <i>' . e($pub) . '</i>' : '';
            $lines[] = "• {$title}{$pubTxt} × {$qty} — {$sub} د.م";
        }

        $lines[] = '';
        $lines[] = "💰 <b>الإجمالي: {$total} د.م</b>";

        return implode("\n", $lines);
    }

    /** Buttons for every other status (two per row), plus a dashboard link. */
    public function keyboard(Order $order): array
    {
        $buttons = [];

        foreach ($order->status->allowedTransitions() as $st) {
            $buttons[] = [
                'text'          => $this->emoji($st) . ' ' . $st->label(),
                'callback_data' => "ord:{$order->id}:{$st->value}",
            ];
        }

        $rows = array_chunk($buttons, 2);

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

    /**
     * Cover photos for the order, as JPEG binaries with captions, ready for a
     * Telegram album. Runs on the queue worker (not the web request). Telegram
     * rejects .webp, so each local cover is converted to JPEG via GD. Books
     * without a usable local cover are skipped; capped at Telegram's 10/album.
     *
     * @return array<int, array{data: string, caption: string}>
     */
    public function albumItems(Order $order): array
    {
        if (!function_exists('imagecreatefromwebp')) {
            return [];
        }

        $order->loadMissing(['orderDetails.book.publishingHouse']);
        $items = [];

        foreach ($order->orderDetails as $detail) {
            $book = $detail->book;
            if (!$book || empty($book->image)) {
                continue;
            }

            $path = public_path($book->image);
            if (!is_file($path)) {
                continue;
            }

            $jpeg = $this->toJpeg($path);
            if ($jpeg === null) {
                continue;
            }

            $pub     = $book->publishingHouse?->name;
            $caption = '<b>' . e($book->title ?? '') . '</b>' . ($pub ? ' — ' . e($pub) : '');

            $items[] = ['data' => $jpeg, 'caption' => $caption];

            if (count($items) >= 10) {
                break;
            }
        }

        return $items;
    }

    /** Decode a local image (webp/png/jpg/gif), flatten onto white, downscale, return JPEG bytes. */
    private function toJpeg(string $path, int $maxDim = 800): ?string
    {
        try {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $img = match ($ext) {
                'webp'        => @imagecreatefromwebp($path),
                'png'         => @imagecreatefrompng($path),
                'jpg', 'jpeg' => @imagecreatefromjpeg($path),
                'gif'         => @imagecreatefromgif($path),
                default       => null,
            };
            if (!$img) {
                return null;
            }

            $w = imagesx($img);
            $h = imagesy($img);
            $scale = min(1, $maxDim / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));

            $canvas = imagecreatetruecolor($nw, $nh);
            $white  = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            imagejpeg($canvas, null, 85);
            $data = ob_get_clean();

            imagedestroy($img);
            imagedestroy($canvas);

            return $data ?: null;
        } catch (\Throwable $e) {
            return null;
        }
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
