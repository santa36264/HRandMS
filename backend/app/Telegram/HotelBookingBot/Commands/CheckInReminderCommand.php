<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class CheckInReminderCommand
{
    /**
     * Chat ID (Telegram user ID)
     */
    private int $chatId;

    /**
     * User ID
     */
    private int $userId;

    /**
     * Hotel name
     */
    private string $hotelName = 'SATAAB Hotel';

    /**
     * Hotel address
     */
    private string $hotelAddress = 'Addis Ababa, Ethiopia';

    /**
     * Hotel phone
     */
    private string $hotelPhone = '+251911234567';

    /**
     * Check-in time
     */
    private string $checkInTime = '14:00';

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Send 24-hour reminder
     */
    public function send24HourReminder(Booking $booking): array
    {
        try {
            $message = $this->format24HourReminder($booking);
            $buttons = $this->get24HourButtons($booking);

            // Send message via Telegram API
            $this->sendTelegramMessage($message, $buttons);

            Log::info('24-hour check-in reminder sent', [
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
                'chat_id' => $this->chatId,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending 24-hour reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error sending reminder',
            ];
        }
    }

    /**
     * Send 2-hour reminder
     */
    public function send2HourReminder(Booking $booking): array
    {
        try {
            $message = $this->format2HourReminder($booking);
            $buttons = $this->get2HourButtons($booking);

            // Send message via Telegram API
            $this->sendTelegramMessage($message, $buttons);

            // Send QR code
            $this->sendQRCode($booking);

            Log::info('2-hour check-in reminder sent', [
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
                'chat_id' => $this->chatId,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending 2-hour reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error sending reminder',
            ];
        }
    }

    /**
     * Format 24-hour reminder message
     */
    private function format24HourReminder(Booking $booking): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));

        $message = "🔔 <b>Check-in Reminder!</b>\n\n";
        $message .= "You're checking in tomorrow at <b>{$this->hotelName}</b>\n\n";

        $message .= "🛏️ <b>Room Details:</b>\n";
        $message .= "   Type: {$booking->room->type}\n";
        $message .= "   Name: {$booking->room->name}\n\n";

        $message .= "⏰ <b>Check-in Time:</b> {$this->checkInTime}\n\n";

        $message .= "📌 <b>Booking Reference:</b> #{$ref}\n\n";

        $message .= "📋 <b>What to Bring:</b>\n";
        $message .= "   • Valid ID/Passport\n";
        $message .= "   • Booking reference\n";
        $message .= "   • Payment method (if not pre-paid)\n\n";

        $message .= "📍 <b>Hotel Location:</b>\n";
        $message .= "   {$this->hotelAddress}\n\n";

        $message .= "📞 <b>Hotel Contact:</b>\n";
        $message .= "   Phone: {$this->hotelPhone}\n";
        $message .= "   Email: info@sataabhotel.com\n\n";

        $message .= "See you tomorrow! 🏨";

        return $message;
    }

    /**
     * Format 2-hour reminder message
     */
    private function format2HourReminder(Booking $booking): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));

        $message = "⏰ <b>Your Room is Ready!</b>\n\n";
        $message .= "Welcome to <b>{$this->hotelName}</b>!\n\n";

        $message .= "Your room is now ready for check-in.\n\n";

        $message .= "🛏️ <b>Room:</b> {$booking->room->name} ({$booking->room->type})\n";
        $message .= "📌 <b>Reference:</b> #{$ref}\n\n";

        $message .= "🔑 <b>Digital Check-in:</b>\n";
        $message .= "Click the button below to complete your check-in digitally.\n";
        $message .= "Show the QR code at the front desk.\n\n";

        $message .= "📍 <b>We're Located At:</b>\n";
        $message .= "{$this->hotelAddress}\n\n";

        $message .= "See you at the front desk! 👋";

        return $message;
    }

    /**
     * Get 24-hour reminder buttons
     */
    private function get24HourButtons(Booking $booking): array
    {
        $mapsUrl = "https://maps.google.com/?q=" . urlencode($this->hotelAddress);
        $phoneUrl = "tel:" . str_replace([' ', '-', '+'], '', $this->hotelPhone);

        return [
            [
                ['text' => '📍 Directions', 'url' => $mapsUrl],
                ['text' => '📞 Call Hotel', 'url' => $phoneUrl],
            ],
            [
                ['text' => '📖 View Booking', 'callback_data' => "mybooking_view_{$booking->id}"],
            ],
        ];
    }

    /**
     * Get 2-hour reminder buttons
     */
    private function get2HourButtons(Booking $booking): array
    {
        return [
            [
                ['text' => '🔑 Digital Check-in', 'callback_data' => "booking_detail_qr_{$booking->id}"],
            ],
            [
                ['text' => '📍 Directions', 'url' => "https://maps.google.com/?q=" . urlencode($this->hotelAddress)],
                ['text' => '📞 Call Hotel', 'url' => "tel:" . str_replace([' ', '-', '+'], '', $this->hotelPhone)],
            ],
        ];
    }

    /**
     * Send Telegram message
     */
    private function sendTelegramMessage(string $message, array $buttons): void
    {
        try {
            $bot = app('telegram.bot');
            
            $bot->sendMessage([
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $buttons,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending Telegram message', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send QR code
     */
    private function sendQRCode(Booking $booking): void
    {
        try {
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking);
            $qrCodeUrl = $formatter->getQRCodeUrl();

            $bot = app('telegram.bot');

            $bot->sendPhoto([
                'chat_id' => $this->chatId,
                'photo' => $qrCodeUrl,
                'caption' => "🔑 <b>Check-in QR Code</b>\n\n"
                    . "Show this QR code at the front desk to complete your check-in.",
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending QR code', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Set hotel name
     */
    public function setHotelName(string $name): self
    {
        $this->hotelName = $name;
        return $this;
    }

    /**
     * Set hotel address
     */
    public function setHotelAddress(string $address): self
    {
        $this->hotelAddress = $address;
        return $this;
    }

    /**
     * Set hotel phone
     */
    public function setHotelPhone(string $phone): self
    {
        $this->hotelPhone = $phone;
        return $this;
    }

    /**
     * Set check-in time
     */
    public function setCheckInTime(string $time): self
    {
        $this->checkInTime = $time;
        return $this;
    }
}
