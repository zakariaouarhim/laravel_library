<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--delete : Remove the webhook instead of setting it}';

    protected $description = 'Register (or delete) the Telegram bot webhook for order-status buttons';

    public function handle(TelegramService $telegram): int
    {
        if (empty(config('services.telegram.bot_token'))) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');
            return self::FAILURE;
        }

        if ($this->option('delete')) {
            $res = $telegram->deleteWebhook();
            $this->info('deleteWebhook: ' . json_encode($res, JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $secret = (string) config('services.telegram.webhook_secret');
        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set.');
            return self::FAILURE;
        }

        $url = route('telegram.webhook', ['secret' => $secret]);
        $res = $telegram->setWebhook($url, $secret);

        $this->info('Webhook URL: ' . $url);
        $this->info('setWebhook:  ' . json_encode($res, JSON_UNESCAPED_UNICODE));

        return ($res['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
