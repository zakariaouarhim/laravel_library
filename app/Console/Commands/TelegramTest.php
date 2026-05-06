<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramTest extends Command
{
    protected $signature = 'telegram:test {--chat= : Override the admin chat id}';

    protected $description = 'Send a test message via the configured Telegram bot to verify setup';

    public function handle(TelegramService $telegram): int
    {
        $token   = config('services.telegram.bot_token');
        $chat    = $this->option('chat') ?: config('services.telegram.admin_chat_id');

        if (empty($token)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return self::FAILURE;
        }
        if (empty($chat)) {
            $this->error('TELEGRAM_ADMIN_CHAT_ID is not set in .env (or pass --chat=...)');
            return self::FAILURE;
        }

        $message = "✅ <b>Test message from " . config('app.name') . "</b>\n"
                 . "If you can read this, the Telegram bot is wired up correctly.\n"
                 . "Time: " . now()->toDateTimeString();

        $ok = $telegram->sendMessage($message, $chat);

        if ($ok) {
            $this->info('Telegram test message sent successfully.');
            return self::SUCCESS;
        }

        $this->error('Telegram send failed. Check storage/logs/laravel.log for details.');
        return self::FAILURE;
    }
}
