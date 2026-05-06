<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $message, ?string $chatId = null): bool
    {
        $token  = config('services.telegram.bot_token');
        $target = $chatId ?? config('services.telegram.admin_chat_id');

        if (empty($token) || empty($target)) {
            Log::warning('Telegram not configured — skipping notification');
            return false;
        }

        try {
            $response = Http::timeout(10)->post(
                "https://api.telegram.org/bot{$token}/sendMessage",
                [
                    'chat_id'                  => $target,
                    'text'                     => $message,
                    'parse_mode'               => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            );

            if (!$response->successful()) {
                Log::error('Telegram sendMessage failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
