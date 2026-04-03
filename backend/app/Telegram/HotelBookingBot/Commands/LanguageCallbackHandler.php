<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class LanguageCallbackHandler
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
     * Handle language callback
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

            $language = $parts[1];

            $command = new LanguageCommand($this->chatId, $this->userId);
            $result = $command->changeLanguage($language);

            return [
                'success' => $result['success'],
                'callback_id' => $this->callbackId,
                'response' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling language callback', [
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
}
