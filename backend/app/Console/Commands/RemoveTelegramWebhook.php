<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class RemoveTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:remove-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Telegram webhook and switch to polling mode';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = config('telegram.bots.default.token');

        if (!$token) {
            $this->error('❌ TELEGRAM_BOT_TOKEN is not set in .env file');
            return 1;
        }

        if (!$this->confirm('Are you sure you want to remove the webhook? The bot will switch to polling mode.')) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->info('Removing Telegram webhook...');

        try {
            $telegramService = new TelegramService();
            
            if ($telegramService->removeWebhook()) {
                $this->info('✅ Webhook removed successfully!');
                $this->line('The bot will now use polling mode.');
                $this->line('Run: php artisan telegram:polling');

                return 0;
            } else {
                $this->error('❌ Failed to remove webhook');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
