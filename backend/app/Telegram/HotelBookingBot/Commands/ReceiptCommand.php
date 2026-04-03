<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Telegram\HotelBookingBot\Formatters\DigitalReceiptFormatter;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\Log;

class ReceiptCommand
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
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Send digital receipt
     */
    public function sendReceipt(Booking $booking, ?Payment $payment = null): array
    {
        try {
            $formatter = new DigitalReceiptFormatter($booking, $payment);

            // Get receipt data
            $receiptMessage = $formatter->getFormattedReceipt();
            $buttons = $formatter->getReceiptButtons();
            $qrCodeUrl = $formatter->getQRCodeUrl();
            $emailData = $formatter->getEmailSummary();

            // Send confirmation email
            $this->sendConfirmationEmail($booking, $emailData);

            return [
                'success' => true,
                'message' => $receiptMessage,
                'buttons' => $buttons,
                'qr_code_url' => $qrCodeUrl,
                'email_data' => $emailData,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending receipt', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error generating receipt. Please try again.',
            ];
        }
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail(Booking $booking, array $emailData): void
    {
        try {
            $booking->user->notify(new BookingConfirmedNotification($booking, $emailData));
        } catch (\Exception $e) {
            Log::error('Error sending confirmation email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get calendar file
     */
    public function getCalendarFile(Booking $booking): array
    {
        try {
            $formatter = new DigitalReceiptFormatter($booking);
            $icalContent = $formatter->getICalendarFormat();
            $bookingRef = strtoupper(substr(md5($booking->id), 0, 8));

            return [
                'success' => true,
                'filename' => "booking_{$bookingRef}.ics",
                'content' => $icalContent,
                'mime_type' => 'text/calendar',
            ];
        } catch (\Exception $e) {
            Log::error('Error generating calendar file', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error generating calendar file.',
            ];
        }
    }

    /**
     * Get QR code image
     */
    public function getQRCode(Booking $booking): array
    {
        try {
            $formatter = new DigitalReceiptFormatter($booking);
            $qrCodeUrl = $formatter->getQRCodeUrl();

            return [
                'success' => true,
                'url' => $qrCodeUrl,
                'data' => $formatter->getQRCodeData(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating QR code', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error generating QR code.',
            ];
        }
        }

    /**
     * Get booking details for display
     */
    public function getBookingDetails(int $bookingId): array
    {
        try {
            $booking = Booking::with(['user', 'room', 'payments'])->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();

            $formatter = new DigitalReceiptFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getFormattedReceipt(),
                'buttons' => $formatter->getReceiptButtons(),
                'qr_code_url' => $formatter->getQRCodeUrl(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching booking details', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }
}
