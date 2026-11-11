<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $message, ?string $chatId = null, ?array $replyMarkup = null): bool
    {
        $target = $chatId ?? config('services.telegram.admin_chat_id');

        if (empty($target)) {
            Log::warning('Telegram not configured — skipping notification');
            return false;
        }

        $params = [
            'chat_id'                  => $target,
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendMessage', $params) !== null;
    }

    /** Toast/alert shown to the user who tapped an inline button. */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): bool
    {
        $params = ['callback_query_id' => $callbackQueryId, 'show_alert' => $showAlert];
        if ($text !== null) {
            $params['text'] = $text;
        }

        return $this->request('answerCallbackQuery', $params) !== null;
    }

    /** Replace the text (and optionally the keyboard) of an existing message. */
    public function editMessageText($chatId, int $messageId, string $text, ?array $replyMarkup = null): bool
    {
        $params = [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('editMessageText', $params) !== null;
    }

    /** Swap only the inline keyboard of an existing message (text unchanged). */
    public function editMessageReplyMarkup($chatId, int $messageId, ?array $replyMarkup = null): bool
    {
        return $this->request('editMessageReplyMarkup', [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'reply_markup' => json_encode($replyMarkup ?? ['inline_keyboard' => []]),
        ]) !== null;
    }

    public function setWebhook(string $url, ?string $secretToken = null): ?array
    {
        $params = [
            'url'             => $url,
            'allowed_updates' => json_encode(['callback_query', 'message']),
        ];
        if (!empty($secretToken)) {
            $params['secret_token'] = $secretToken;
        }

        return $this->request('setWebhook', $params);
    }

    public function deleteWebhook(): ?array
    {
        return $this->request('deleteWebhook', ['drop_pending_updates' => false]);
    }

    public function getWebhookInfo(): ?array
    {
        return $this->request('getWebhookInfo', []);
    }

    /** Low-level Bot API call; returns the decoded response, or null on failure. */
    private function request(string $method, array $params): ?array
    {
        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            Log::warning("Telegram bot token missing — skipping {$method}");
            return null;
        }

        try {
            $response = Http::timeout(10)->asForm()->post(
                "https://api.telegram.org/bot{$token}/{$method}",
                $params
            );

            if (!$response->successful()) {
                Log::error("Telegram {$method} failed", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("Telegram {$method} exception", ['error' => $e->getMessage()]);
            return null;
        }
    }
}
