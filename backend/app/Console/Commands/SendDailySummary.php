<?php

namespace App\Console\Commands;

use App\Services\StaffNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailySummary extends Command
{
    protected $signature = 'staff:daily-summary';
    protected $description = 'Send daily operations summary to staff group';

    public function handle()
    {
        try {
            $service = new StaffNotificationService();
            $service->sendDailySummary();

            $this->info('Daily summary sent successfully.');
        } catch (\Exception $e) {
            Log::error('Error sending daily summary', ['error' => $e->getMessage()]);
            $this->error('Error sending summary: ' . $e->getMessage());
        }
    }
}
