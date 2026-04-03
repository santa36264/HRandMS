<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConciergeCallbackHandler
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
     * Handle concierge callback
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
                'main' => $this->handleMain(),
                'airport' => $this->handleServiceType('airport'),
                'taxi' => $this->handleServiceType('taxi'),
                'tour' => $this->handleServiceType('tour'),
                'food' => $this->handleServiceType('food'),
                'spa' => $this->handleServiceType('spa'),
                'select' => $this->handleSelectService($parts),
                'time' => $this->handleTimeSlot($parts),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling concierge callback', [
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
     * Handle main menu
     */
    private function handleMain(): array
    {
        $command = new ConciergeCommand($this->chatId, $this->userId);
        return $command->showMainMenu();
    }

    /**
     * Handle service type selection
     */
    private function handleServiceType(string $type): array
    {
        $command = new ConciergeCommand($this->chatId, $this->userId);
        return $command->showServicesByType($type);
    }

    /**
     * Handle service selection
     */
    private function handleSelectService(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid service ID'];
        }

        $serviceId = (int)$parts[2];
        $command = new ConciergeCommand($this->chatId, $this->userId);

        // Store selected service in cache for later confirmation
        Cache::put("concierge_service_{$this->userId}", $serviceId, now()->addHours(1));

        return $command->showServiceDetails($serviceId);
    }

    /**
     * Handle time slot selection
     */
    private function handleTimeSlot(array $parts): array
    {
        if (count($parts) < 4) {
            return ['message' => '❌ Invalid time slot'];
        }

        $serviceId = (int)$parts[2];
        $timeSlot = $parts[3];

        $command = new ConciergeCommand($this->chatId, $this->userId);

        // Store time slot in cache
        Cache::put("concierge_time_{$this->userId}", $timeSlot, now()->addHours(1));

        // Confirm booking
        return $command->confirmBooking($serviceId, $timeSlot);
    }
}
