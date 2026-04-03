<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class VerifyTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:verify-webhook {--bot=default : Bot to verify (default, admin, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Telegram webhook configuration and status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $botType = $this->option('bot');
        $bots = $botType === 'all' ? ['default', 'admin'] : [$botType];

        foreach ($bots as $bot) {
            $this->verifyBot($bot);
            $this->line('');
        }

        return 0;
    }

    /**
     * Verify a specific bot
     */
    private function verifyBot(string $bot): void
    {
        $this->info("=== {$bot} Bot Verification ===");

        try {
            $telegramService = new TelegramService($bot);

            // Get bot info
            $botInfo = $telegramService->getMe();
            if ($botInfo) {
                $this->line("✅ Bot Connected");
                $this->line("   Username: @{$botInfo['username']}");
                $this->line("   Name: {$botInfo['first_name']}");
            } else {
                $this->error("❌ Failed to connect to bot");
                return;
            }

            // Get webhook info
            $webhookInfo = $telegramService->getWebhookInfo();
            if ($webhookInfo) {
                $this->line("✅ Webhook Status");
                $this->line("   URL: " . ($webhookInfo['url'] ?? 'Not set'));
                $this->line("   Pending Updates: " . ($webhookInfo['pending_update_count'] ?? 0));
                $this->line("   IP Address: " . ($webhookInfo['ip_address'] ?? 'N/A'));
                
                if (isset($webhookInfo['last_error_date']) && $webhookInfo['last_error_date']) {
                    $this->warn("   Last Error: " . ($webhookInfo['last_error_message'] ?? 'Unknown'));
                } else {
                    $this->line("   Last Error: None");
                }
            } else {
                $this->error("❌ Failed to get webhook info");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
