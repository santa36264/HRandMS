<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetupNgrokWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-ngrok {ngrok_url : Your ngrok URL (e.g., https://abc123.ngrok.io)} {--bot=default : Bot to setup (default or admin)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Telegram webhook with ngrok URL for local testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ngrokUrl = rtrim($this->argument('ngrok_url'), '/');
        $bot = $this->option('bot');

        // Validate ngrok URL
        if (!str_starts_with($ngrokUrl, 'https://')) {
            $this->error('❌ ngrok URL must start with https://');
            return 1;
        }

        // Build webhook URL
        $webhookPath = $bot === 'admin' ? '/api/telegram-webhook/admin' : '/api/telegram-webhook';
        $webhookUrl = $ngrokUrl . $webhookPath;

        $this->info("Setting up {$bot} bot with ngrok...");
        $this->line("ngrok URL: {$ngrokUrl}");
        $this->line("Webhook URL: {$webhookUrl}");
        $this->line('');

        if (!$this->confirm('Continue with this configuration?')) {
            $this->info('Cancelled.');
            return 0;
        }

        try {
            $telegramService = new TelegramService($bot);
            
            if ($telegramService->setWebhook($webhookUrl)) {
                $this->info("✅ {$bot} bot webhook set successfully!");
                $this->line("Webhook URL: {$webhookUrl}");
                $this->line('');
                $this->info('Next steps:');
                $this->line('1. Keep ngrok running in another terminal');
                $this->line('2. Test the bot on Telegram');
                $this->line('3. Check logs: tail -f storage/logs/laravel.log');
                $this->line('4. Verify webhook: php artisan telegram:verify-webhook --bot=' . $bot);

                return 0;
            } else {
                $this->error("❌ Failed to set {$bot} bot webhook");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
