<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--bot=default : Bot to set webhook for (default, admin, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Telegram webhook URL for the bot(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $botType = $this->option('bot');
        $bots = $botType === 'all' ? ['default', 'admin'] : [$botType];

        $success = true;

        foreach ($bots as $bot) {
            if (!$this->setWebhookForBot($bot)) {
                $success = false;
            }
        }

        return $success ? 0 : 1;
    }

    /**
     * Set webhook for a specific bot
     */
    private function setWebhookForBot(string $bot): bool
    {
        $token = config("telegram.bots.{$bot}.token");
        $webhookUrl = $bot === 'admin' 
            ? config('telegram.admin_webhook_url')
            : config('telegram.webhook_url');

        if (!$token) {
            $this->error("❌ TELEGRAM_" . strtoupper($bot) . "_BOT_TOKEN is not set");
            return false;
        }

        if (!$webhookUrl) {
            $this->error("❌ TELEGRAM_" . strtoupper($bot) . "_WEBHOOK_URL is not set");
            return false;
        }

        $this->info("Setting {$bot} bot webhook...");
        $this->line("Token: " . substr($token, 0, 10) . '...');
        $this->line("Webhook URL: {$webhookUrl}");

        try {
            $telegramService = new TelegramService($bot);
            
            if ($telegramService->setWebhook($webhookUrl)) {
                $this->info("✅ {$bot} bot webhook set successfully!");
                return true;
            } else {
                $this->error("❌ Failed to set {$bot} bot webhook");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error setting {$bot} bot webhook: " . $e->getMessage());
            return false;
        }
    }
}
