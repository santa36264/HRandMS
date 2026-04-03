<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Payments\DTOs\PaymentRequestDTO;
use App\Payments\Gateways\ChapaGateway;
use Illuminate\Support\Facades\Log;

class PaymentCommand
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
     * Chapa gateway
     */
    private ChapaGateway $chapaGateway;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId, ChapaGateway $chapaGateway)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->chapaGateway = $chapaGateway;
    }

    /**
     * Show payment options
     */
    public function showPaymentOptions(): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (empty($data)) {
            return [
                'success' => false,
                'message' => '❌ Booking session expired. Please start over.',
            ];
        }

        $message = "💳 <b>Payment Options</b>\n\n";
        $message .= "Select your preferred payment method:\n\n";
        $message .= "Total Amount: <b>{$data['total_price']} ETB</b>\n\n";
        $message .= "Available payment methods:";

        $buttons = [
            [
                ['text' => '💳 Pay with Chapa', 'callback_data' => 'payment_chapa'],
            ],
            [
                ['text' => '📱 Telebirr', 'callback_data' => 'payment_telebirr'],
            ],
            [
                ['text' => '🏦 CBE Birr', 'callback_data' => 'payment_cbe'],
            ],
            [
                ['text' => '❌ Cancel', 'callback_data' => 'payment_cancel'],
            ],
        ];

        return [
            'success' => true,
            'message' => $message,
            'buttons' => $buttons,
        ];
    }

    /**
     * Initiate Chapa payment
     */
    public function initiateChapaPayment(): array
    {
        try {
            $stateManager = new BookingStateManager($this->userId);
            $bookingData = $stateManager->getBookingData();

            if (empty($bookingData)) {
                return [
                    'success' => false,
                    'message' => '❌ Booking session expired. Please start over.',
                ];
            }

            $user = User::where('telegram_user_id', $this->userId)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '❌ User not found. Please register first.',
                ];
            }

            // Create booking record with pending_payment status
            $booking = Booking::create([
                'user_id' => $user->id,
                'room_id' => $bookingData['room_id'],
                'check_in_date' => $bookingData['check_in_date'],
                'check_out_date' => $bookingData['check_out_date'],
                'guest_count' => $bookingData['guest_count'],
                'guest_names' => json_encode($bookingData['guest_names']),
                'special_requests' => $bookingData['special_requests'],
                'total_price' => $bookingData['total_price'],
                'status' => 'pending_payment',
            ]);

            // Prepare payment request
            $paymentRequest = new PaymentRequestDTO(
                amount: (int)$bookingData['total_price'],
                currency: 'ETB',
                email: $user->email,
                first_name: $user->name,
                phone_number: $user->phone,
                tx_ref: 'booking_' . $booking->id . '_' . time(),
                description: "Hotel Booking - {$bookingData['room_name']} ({$bookingData['nights']} nights)",
                return_url: config('app.url') . '/api/payments/chapa/callback',
                customization: [
                    'title' => 'SATAAB Hotel Booking',
                    'description' => $bookingData['room_name'],
                ]
            );

            // Initiate payment with Chapa
            $paymentResponse = $this->chapaGateway->initiate($paymentRequest);

            if (!$paymentResponse->isSuccessful()) {
                $booking->update(['status' => 'payment_failed']);
                return [
                    'success' => false,
                    'message' => '❌ Failed to initiate payment. Please try again.',
                ];
            }

            // Store payment record
            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'gateway' => 'chapa',
                'amount' => $bookingData['total_price'],
                'currency' => 'ETB',
                'transaction_ref' => $paymentResponse->getTransactionReference(),
                'status' => 'pending',
                'metadata' => json_encode([
                    'checkout_url' => $paymentResponse->getCheckoutUrl(),
                    'tx_ref' => $paymentRequest->tx_ref,
                ]),
            ]);

            // Store payment state
            $stateManager->updateBookingData([
                'booking_id' => $booking->id,
                'payment_tx_ref' => $paymentResponse->getTransactionReference(),
                'payment_method' => 'chapa',
            ]);
            $stateManager->setState('awaiting_payment_confirmation');

            return [
                'success' => true,
                'message' => "💳 <b>Payment Initiated</b>\n\n"
                    . "Transaction Reference: <b>{$paymentResponse->getTransactionReference()}</b>\n\n"
                    . "Amount: <b>{$bookingData['total_price']} ETB</b>\n\n"
                    . "Click the button below to complete payment:",
                'checkout_url' => $paymentResponse->getCheckoutUrl(),
                'tx_ref' => $paymentResponse->getTransactionReference(),
                'booking_id' => $booking->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error initiating Chapa payment', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error initiating payment. Please try again.',
            ];
        }
    }

    /**
     * Handle payment confirmation
     */
    public function handlePaymentConfirmation(string $txRef): array
    {
        try {
            // Verify payment with Chapa
            $paymentResponse = $this->chapaGateway->verify($txRef);

            if (!$paymentResponse->isSuccessful()) {
                return [
                    'success' => false,
                    'message' => '❌ Payment verification failed. Please try again.',
                ];
            }

            // Find and update payment record
            $payment = Payment::where('transaction_ref', $txRef)->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => '❌ Payment record not found.',
                ];
            }

            $payment->update([
                'status' => 'completed',
                'metadata' => json_encode(array_merge(
                    json_decode($payment->metadata, true) ?? [],
                    ['verified_at' => now()]
                )),
            ]);

            // Update booking status
            $booking = $payment->booking;
            $booking->update(['status' => 'confirmed']);

            // Clear booking state
            $stateManager = new BookingStateManager($this->userId);
            $stateManager->clearState();

            // Generate and send receipt
            $receiptCommand = new \App\Telegram\HotelBookingBot\Commands\ReceiptCommand(
                $this->chatId,
                $this->userId
            );

            $receiptResult = $receiptCommand->sendReceipt($booking, $payment);

            if ($receiptResult['success']) {
                return [
                    'success' => true,
                    'message' => $receiptResult['message'],
                    'buttons' => $receiptResult['buttons'],
                    'qr_code_url' => $receiptResult['qr_code_url'],
                    'booking_id' => $booking->id,
                ];
            }

            return [
                'success' => true,
                'message' => "✅ <b>Booking Confirmed!</b>\n\n"
                    . "Your booking has been confirmed.\n"
                    . "A confirmation email has been sent to your registered email address.",
                'booking_id' => $booking->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling payment confirmation', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing payment confirmation. Please try again.',
            ];
        }
    }



    /**
     * Handle payment failure
     */
    public function handlePaymentFailure(string $txRef): array
    {
        try {
            $payment = Payment::where('transaction_ref', $txRef)->first();

            if ($payment) {
                $payment->update(['status' => 'failed']);
                $payment->booking->update(['status' => 'payment_failed']);
            }

            return [
                'success' => true,
                'message' => "❌ <b>Payment Failed</b>\n\n"
                    . "Your payment could not be processed.\n\n"
                    . "You can:\n"
                    . "1. Retry the payment\n"
                    . "2. Try a different payment method\n"
                    . "3. Contact support for assistance",
                'action' => 'payment_failed',
            ];
        } catch (\Exception $e) {
            Log::error('Error handling payment failure', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing payment failure.',
            ];
        }
    }

    /**
     * Get payment retry buttons
     */
    public function getPaymentRetryButtons(): array
    {
        return [
            [
                ['text' => '🔄 Retry Payment', 'callback_data' => 'payment_retry'],
                ['text' => '💳 Different Method', 'callback_data' => 'payment_method_select'],
            ],
            [
                ['text' => '📞 Contact Support', 'callback_data' => 'menu_contact'],
            ],
        ];
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (isset($data['booking_id'])) {
            $booking = Booking::find($data['booking_id']);
            if ($booking) {
                $booking->update(['status' => 'cancelled']);
            }
        }

        $stateManager->clearState();

        return [
            'success' => true,
            'message' => "❌ Payment cancelled.\n\n"
                . "Your booking has been cancelled.\n"
                . "You can start a new search anytime using /search command.",
        ];
    }
}
