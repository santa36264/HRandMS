<?php

namespace App\Console\Commands;

use App\Services\ReviewAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateReviewAnalytics extends Command
{
    protected $signature = 'analytics:generate-reviews';
    protected $description = 'Generate review analytics and check for negative reviews';

    public function handle()
    {
        try {
            $service = new ReviewAnalyticsService();

            // Generate daily analytics
            $service->generateDailyAnalytics();

            // Update room type ratings
            $service->updateRoomTypeRatings();

            // Check for negative reviews
            $service->checkNegativeReviews();

            $this->info('Review analytics generated successfully.');
        } catch (\Exception $e) {
            Log::error('Error generating review analytics', ['error' => $e->getMessage()]);
            $this->error('Error generating analytics: ' . $e->getMessage());
        }
    }
}
