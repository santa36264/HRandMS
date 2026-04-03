<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class CheckOutCallbackHandler
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
     * Handle check-out callback
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
                'extend' => $this->handleExtend($parts),
                'bill' => $this->handleBill($parts),
                'confirm' => $this->handleConfirm($parts),
                'pay' => $this->handlePay($parts),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling check-out callback', [
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
     * Handle extend stay
     */
    private function handleExtend(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CheckOutReminderCommand($this->chatId, $this->userId);

        return $command->handleExtendStay($bookingId);
    }

    /**
     * Handle bill request
     */
    private function handleBill(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CheckOutReminderCommand($this->chatId, $this->userId);

        return $command->handleBillRequest($bookingId);
    }

    /**
     * Handle express check-out
     */
    private function handleConfirm(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CheckOutReminderCommand($this->chatId, $this->userId);

        return $command->handleExpressCheckOut($bookingId);
    }

    /**
     * Handle payment
     */
    private function handlePay(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CheckOutReminderCommand($this->chatId, $this->userId);

        return $command->handlePaymentRequest($bookingId);
    }
}
