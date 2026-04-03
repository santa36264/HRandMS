<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Services\RoomService;
use Illuminate\Support\Facades\Log;

class BookingCallbackHandler
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
     * Room service
     */
    private RoomService $roomService;

    /**
     * Constructor
     */
    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId,
        RoomService $roomService
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
        $this->roomService = $roomService;
    }

    /**
     * Handle booking callback
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
                'confirm' => $this->handleConfirm(),
                'edit' => $this->handleEdit(),
                'cancel' => $this->handleCancel(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling booking callback', [
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
     * Handle confirm
     */
    private function handleConfirm(): array
    {
        $paymentCommand = new PaymentCommand(
            $this->chatId,
            $this->userId,
            app('App\Payments\Gateways\ChapaGateway')
        );

        return $paymentCommand->showPaymentOptions();
    }

    /**
     * Handle edit
     */
    private function handleEdit(): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (empty($data)) {
            return [
                'message' => '❌ Booking session expired. Please start over.',
            ];
        }

        // Reset to guest count step for editing
        $stateManager->setState('awaiting_guest_count');

        return [
            'message' => "✏️ <b>Edit Booking</b>\n\n"
                . "What would you like to edit?\n\n"
                . "Current details:\n"
                . "👥 Guests: {$data['guest_count']}\n"
                . "💰 Total: {$data['total_price']} ETB\n\n"
                . "Please enter the new number of guests:",
            'action' => 'awaiting_guest_count',
        ];
    }

    /**
     * Handle cancel
     */
    private function handleCancel(): array
    {
        $bookingCommand = new BookingCommand(
            $this->roomService,
            $this->chatId,
            $this->userId
        );

        return $bookingCommand->cancelBooking();
    }
}
