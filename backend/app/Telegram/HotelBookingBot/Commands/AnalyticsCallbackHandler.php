<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class AnalyticsCallbackHandler
{
    private int $chatId;
    private int $userId;
    private string $callbackData;
    private string $callbackId;

    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
    }

    /**
     * Handle analytics callback
     */
    public function handle(): array
    {
        try {
            $parts = explode('_', $this->callbackData);

            if (count($parts) < 2) {
                return [
                    'success' => false,
                    'callback_id' => $this->callbackId,
                    'message' => '❌ Invalid callback data',
                ];
            }

            $action = $parts[1];

            $response = match ($action) {
                'dashboard' => $this->handleDashboard(),
                'detailed' => $this->handleDetailedReport(),
                'rooms' => $this->handleRoomRatings(),
                'negative' => $this->handleNegativeReviews(),
                'export' => $this->handleExport(),
                'ack' => $this->handleAcknowledge($parts),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling analytics callback', [
                'error' => $e->getMessage(),
                'callback_data' => $this->callbackData,
            ]);

            return [
                'success' => false,
                'callback_id' => $this->callbackId,
                'message' => '❌ Error processing request',
            ];
        }
    }

    /**
     * Handle dashboard
     */
    private function handleDashboard(): array
    {
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->showDashboard();
    }

    /**
     * Handle detailed report
     */
    private function handleDetailedReport(): array
    {
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->showDetailedReport();
    }

    /**
     * Handle room ratings
     */
    private function handleRoomRatings(): array
    {
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->showRoomRatings();
    }

    /**
     * Handle negative reviews
     */
    private function handleNegativeReviews(): array
    {
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->showNegativeReviews();
    }

    /**
     * Handle export
     */
    private function handleExport(): array
    {
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->exportReport();
    }

    /**
     * Handle acknowledge
     */
    private function handleAcknowledge(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid alert ID'];
        }

        $alertId = (int)$parts[2];
        $command = new AnalyticsDashboardCommand($this->chatId, $this->userId);
        return $command->acknowledgeNegativeReview($alertId);
    }
}
