<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class GetTelegramWebhookInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:webhook-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get current Telegram webhook information';

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

        $this->info('Fetching webhook information...');

        try {
            $telegramService = new TelegramService();
            $webhook = $telegramService->getWebhookInfo();

            if ($webhook) {
                $this->info('✅ Webhook Information:');
                $this->line('');
                $this->line('URL: ' . ($webhook['url'] ?? 'Not set'));
                $this->line('Has Custom Certificate: ' . ($webhook['has_custom_certificate'] ? 'Yes' : 'No'));
                $this->line('Pending Updates: ' . ($webhook['pending_update_count'] ?? 0));
                $this->line('IP Address: ' . ($webhook['ip_address'] ?? 'N/A'));
                $this->line('Last Error Date: ' . ($webhook['last_error_date'] ?? 'None'));
                $this->line('Last Error Message: ' . ($webhook['last_error_message'] ?? 'None'));
                $this->line('Last Sync Date: ' . ($webhook['last_synchronization_date'] ?? 'N/A'));

                return 0;
            } else {
                $this->error('❌ Failed to get webhook info');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
