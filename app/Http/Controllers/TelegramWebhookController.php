<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderTelegramPresenter;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(
        Request $request,
        string $secret,
        TelegramService $telegram,
        OrderService $orders,
        OrderTelegramPresenter $presenter
    ) {
        $configured = (string) config('services.telegram.webhook_secret');

        // Closed unless a secret is configured; the URL segment AND Telegram's
        // secret-token header must both match it.
        if ($configured === ''
            || !hash_equals($configured, $secret)
            || !hash_equals($configured, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(404);
        }

        $cq = $request->input('callback_query');
        if (!is_array($cq)) {
            return response()->json(['ok' => true]); // only button taps are handled
        }

        $cqId      = (string) ($cq['id'] ?? '');
        $fromId    = (string) data_get($cq, 'from.id');
        $chatId    = data_get($cq, 'message.chat.id');
        $messageId = (int) data_get($cq, 'message.message_id');
        $data      = (string) ($cq['data'] ?? '');
        $adminId   = (string) config('services.telegram.admin_chat_id');

        // Only the configured admin may act.
        if ($adminId === '' || !hash_equals($adminId, $fromId)) {
            $telegram->answerCallbackQuery($cqId, 'غير مصرَّح لك', true);
            return response()->json(['ok' => true]);
        }

        // "تراجع" → restore the normal action buttons.
        if (preg_match('/^ord:(\d+):back$/', $data, $m)) {
            if ($order = Order::find((int) $m[1])) {
                $telegram->editMessageReplyMarkup($chatId, $messageId, $presenter->keyboard($order));
            }
            $telegram->answerCallbackQuery($cqId);
            return response()->json(['ok' => true]);
        }

        //  ord:{id}:{status}    → apply (destructive ones ask to confirm first)
        //  ord:{id}:{status}:y  → confirmed, apply
        if (!preg_match('/^ord:(\d+):([a-z]+)(:y)?$/', $data, $m)) {
            $telegram->answerCallbackQuery($cqId);
            return response()->json(['ok' => true]);
        }

        $orderId   = (int) $m[1];
        $confirmed = isset($m[3]);
        $target    = OrderStatus::tryFrom($m[2]);

        if (!$target) {
            $telegram->answerCallbackQuery($cqId, 'حالة غير معروفة', true);
            return response()->json(['ok' => true]);
        }

        $order = Order::find($orderId);
        if (!$order) {
            $telegram->answerCallbackQuery($cqId, 'الطلب غير موجود', true);
            return response()->json(['ok' => true]);
        }

        // Destructive change → one confirmation tap first.
        if ($presenter->isDestructive($target) && !$confirmed) {
            $telegram->editMessageReplyMarkup($chatId, $messageId, $presenter->confirmKeyboard($order, $target));
            $telegram->answerCallbackQuery($cqId, 'اضغط للتأكيد');
            return response()->json(['ok' => true]);
        }

        try {
            $orders->updateStatus($order, $target, 'تم التغيير عبر تيليجرام');
            $order->refresh();
            $telegram->answerCallbackQuery($cqId, '✓ ' . $order->status->label());
            $telegram->editMessageText($chatId, $messageId, $presenter->text($order), $presenter->keyboard($order));
        } catch (\InvalidArgumentException $e) {
            // Stale button / double tap (e.g. already past this status).
            $telegram->answerCallbackQuery($cqId, $e->getMessage(), true);
            $telegram->editMessageReplyMarkup($chatId, $messageId, $presenter->keyboard($order->refresh()));
        } catch (\Throwable $e) {
            Log::error('Telegram webhook status update failed', ['order' => $orderId, 'error' => $e->getMessage()]);
            $telegram->answerCallbackQuery($cqId, 'حدث خطأ غير متوقع', true);
        }

        return response()->json(['ok' => true]);
    }
}
