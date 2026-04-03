<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Payments\Gateways\ChapaGateway;
use Illuminate\Support\Facades\Log;

class PaymentCallbackHandler
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
     * Chapa gateway
     */
    private ChapaGateway $chapaGateway;

    /**
     * Constructor
     */
    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId,
        ChapaGateway $chapaGateway
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
        $this->chapaGateway = $chapaGateway;
    }

    /**
     * Handle payment callback
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
                'chapa' => $this->handleChapaPayment(),
                'telebirr' => $this->handleTelebirrPayment(),
                'cbe' => $this->handleCBEPayment(),
                'retry' => $this->handleRetryPayment(),
                'method' => $this->handleMethodSelect(),
                'cancel' => $this->handleCancelPayment(),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling payment callback', [
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
     * Handle Chapa payment
     */
    private function handleChapaPayment(): array
    {
        $paymentCommand = new PaymentCommand(
            $this->chatId,
            $this->userId,
            $this->chapaGateway
        );

        return $paymentCommand->initiateChapaPayment();
    }

    /**
     * Handle Telebirr payment
     */
    private function handleTelebirrPayment(): array
    {
        return [
            'message' => "📱 <b>Telebirr Payment</b>\n\n"
                . "Telebirr payment integration coming soon.\n"
                . "Please use Chapa or CBE Birr for now.",
        ];
    }

    /**
     * Handle CBE payment
     */
    private function handleCBEPayment(): array
    {
        return [
            'message' => "🏦 <b>CBE Birr Payment</b>\n\n"
                . "CBE Birr payment integration coming soon.\n"
                . "Please use Chapa for now.",
        ];
    }

    /**
     * Handle retry payment
     */
    private function handleRetryPayment(): array
    {
        $paymentCommand = new PaymentCommand(
            $this->chatId,
            $this->userId,
            $this->chapaGateway
        );

        return $paymentCommand->showPaymentOptions();
    }

    /**
     * Handle method select
     */
    private function handleMethodSelect(): array
    {
        $paymentCommand = new PaymentCommand(
            $this->chatId,
            $this->userId,
            $this->chapaGateway
        );

        return $paymentCommand->showPaymentOptions();
    }

    /**
     * Handle cancel payment
     */
    private function handleCancelPayment(): array
    {
        $paymentCommand = new PaymentCommand(
            $this->chatId,
            $this->userId,
            $this->chapaGateway
        );

        return $paymentCommand->cancelPayment();
    }
}
