<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class PromotionCallbackHandler
{
    /**
     * Chat ID
     */
    private int $chatId;

    /**
     * User ID
     */
    private int $userId;

    /**
     * Callback data
     */
    private string $callbackData;

    /**
     * Callback ID
     */
    private string $callbackId;

    /**
     * Constructor
     */
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
     * Handle promotion callback
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
                'menu' => $this->handleMenu(),
                'toggle' => $this->handleToggle($parts),
                'details' => $this->handleDetails($parts),
                'unsubscribe' => $this->handleUnsubscribe(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling promotion callback', [
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
     * Handle menu
     */
    private function handleMenu(): array
    {
        $command = new PromotionCommand($this->chatId, $this->userId);
        return $command->showPromotionMenu();
    }

    /**
     * Handle toggle
     */
    private function handleToggle(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid promotion type'];
        }

        $type = $parts[2];
        $command = new PromotionCommand($this->chatId, $this->userId);

        return $command->togglePromotion($type);
    }

    /**
     * Handle details
     */
    private function handleDetails(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid promotion ID'];
        }

        $promotionId = (int)$parts[2];
        $command = new PromotionCommand($this->chatId, $this->userId);

        return $command->showPromotionDetails($promotionId);
    }

    /**
     * Handle unsubscribe
     */
    private function handleUnsubscribe(): array
    {
        return [
            'message' => "❌ <b>Unsubscribe from Promotions</b>\n\n"
                . "You will no longer receive promotional messages.\n"
                . "You can re-subscribe anytime from the menu.",
            'buttons' => [[
                ['text' => '✅ Confirm Unsubscribe', 'callback_data' => 'promo_confirm_unsubscribe'],
                ['text' => '🔙 Keep Subscribed', 'callback_data' => 'promo_menu'],
            ]],
        ];
    }
}
