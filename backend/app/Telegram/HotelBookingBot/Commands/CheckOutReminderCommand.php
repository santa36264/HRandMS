<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class CheckOutReminderCommand
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
     * Hotel phone
     */
    private string $hotelPhone = '+251911234567';

    /**
     * Check-out time
     */
    private string $checkOutTime = '11:00';

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Send day-before check-out reminder
     */
    public function sendDayBeforeReminder(Booking $booking): array
    {
        try {
            $message = $this->formatDayBeforeReminder($booking);
            $buttons = $this->getDayBeforeButtons($booking);

            $this->sendTelegramMessage($message, $buttons);

            Log::info('Day-before check-out reminder sent', [
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending day-before reminder', [
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
     * Send check-out day reminder
     */
    public function sendCheckOutDayReminder(Booking $booking): array
    {
        try {
            $message = $this->formatCheckOutDayReminder($booking);
            $buttons = $this->getCheckOutDayButtons($booking);

            $this->sendTelegramMessage($message, $buttons);

            Log::info('Check-out day reminder sent', [
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
            ]);

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error sending check-out day reminder', [
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
     * Format day-before reminder message
     */
    private function formatDayBeforeReminder(Booking $booking): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));
        $nights = $booking->check_out_date->diffInDays($booking->check_in_date);

        $message = "🛎️ <b>Check-out Reminder</b>\n\n";
        $message .= "Your stay at <b>{$this->hotelName}</b> ends tomorrow.\n\n";

        $message .= "📅 <b>Stay Summary:</b>\n";
        $message .= "   Check-in: {$booking->check_in_date->format('M d, Y')}\n";
        $message .= "   Check-out: {$booking->check_out_date->format('M d, Y')} at {$this->checkOutTime}\n";
        $message .= "   Duration: {$nights} night" . ($nights > 1 ? 's' : '') . "\n\n";

        $message .= "🛏️ <b>Room:</b> {$booking->room->name}\n";
        $message .= "📌 <b>Reference:</b> #{$ref}\n\n";

        $message .= "💰 <b>Total Amount:</b> {$booking->total_price} ETB\n\n";

        $message .= "What would you like to do?\n";

        return $message;
    }

    /**
     * Format check-out day reminder message
     */
    private function formatCheckOutDayReminder(Booking $booking): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));

        $message = "👋 <b>Thank You for Staying with Us!</b>\n\n";
        $message .= "We hope you had a wonderful experience at <b>{$this->hotelName}</b>.\n\n";

        $message .= "📌 <b>Booking Reference:</b> #{$ref}\n";
        $message .= "🛏️ <b>Room:</b> {$booking->room->name}\n";
        $message .= "⏰ <b>Check-out Time:</b> {$this->checkOutTime}\n\n";

        $message .= "📝 <b>Your Feedback Matters!</b>\n";
        $message .= "Please share your experience with us. Your review helps us improve our services.\n\n";

        $message .= "🙏 We hope to welcome you back soon!\n";

        return $message;
    }

    /**
     * Get day-before reminder buttons
     */
    private function getDayBeforeButtons(Booking $booking): array
    {
        return [
            [
                ['text' => '📅 Extend Stay', 'callback_data' => "checkout_extend_{$booking->id}"],
            ],
            [
                ['text' => '🧾 Request Bill', 'callback_data' => "checkout_bill_{$booking->id}"],
            ],
            [
                ['text' => '✅ Ready to Check-out', 'callback_data' => "checkout_confirm_{$booking->id}"],
            ],
            [
                ['text' => '📖 View Booking', 'callback_data' => "mybooking_view_{$booking->id}"],
            ],
        ];
    }

    /**
     * Get check-out day buttons
     */
    private function getCheckOutDayButtons(Booking $booking): array
    {
        return [
            [
                ['text' => '⭐ Rate Your Stay', 'callback_data' => "booking_detail_review_{$booking->id}"],
            ],
            [
                ['text' => '📞 Contact Hotel', 'callback_data' => 'menu_contact'],
                ['text' => '📖 View Booking', 'callback_data' => "mybooking_view_{$booking->id}"],
            ],
        ];
    }

    /**
     * Handle extend stay request
     */
    public function handleExtendStay(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            return [
                'success' => true,
                'message' => "📅 <b>Extend Your Stay</b>\n\n"
                    . "Current check-out: {$booking->check_out_date->format('M d, Y')}\n"
                    . "Room: {$booking->room->name}\n\n"
                    . "How many additional nights would you like?",
                'action' => 'awaiting_extend_nights',
            ];
        } catch (\Exception $e) {
            Log::error('Error handling extend stay', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Handle bill request
     */
    public function handleBillRequest(int $bookingId): array
    {
        try {
            $booking = Booking::with(['payments'])->where('user_id', $this->userId)->findOrFail($bookingId);

            $ref = strtoupper(substr(md5($booking->id), 0, 8));
            $nights = $booking->check_out_date->diffInDays($booking->check_in_date);

            $message = "🧾 <b>Your Bill</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $message .= "📌 <b>Booking Reference:</b> #{$ref}\n";
            $message .= "🛏️ <b>Room:</b> {$booking->room->name}\n";
            $message .= "📅 <b>Dates:</b> {$booking->check_in_date->format('M d')} - {$booking->check_out_date->format('M d, Y')}\n";
            $message .= "🌙 <b>Nights:</b> {$nights}\n\n";

            $message .= "💰 <b>Charges:</b>\n";
            $message .= "   Room: {$booking->total_price} ETB\n";
            $message .= "   Tax: Included\n\n";

            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "💵 <b>Total Due:</b> {$booking->total_price} ETB\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            $payment = $booking->payments()->where('status', 'completed')->latest()->first();
            if ($payment) {
                $message .= "✅ <b>Payment Status:</b> Paid\n";
                $message .= "   Method: " . ucfirst($payment->gateway) . "\n";
                $message .= "   Transaction: {$payment->transaction_ref}\n\n";
            } else {
                $message .= "⏳ <b>Payment Status:</b> Pending\n\n";
            }

            $message .= "📧 A detailed bill has been sent to your email.\n";

            $buttons = [
                [
                    ['text' => '💳 Pay Now', 'callback_data' => "checkout_pay_{$bookingId}"],
                    ['text' => '✅ Already Paid', 'callback_data' => "checkout_confirm_{$bookingId}"],
                ],
                [
                    ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling bill request', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Handle express check-out
     */
    public function handleExpressCheckOut(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            if ($booking->status !== 'checked_in') {
                return [
                    'success' => false,
                    'message' => '❌ You are not currently checked in.',
                ];
            }

            // Update booking status
            $booking->update(['status' => 'checked_out']);

            $ref = strtoupper(substr(md5($booking->id), 0, 8));

            $message = "✅ <b>Express Check-out Completed</b>\n\n";
            $message .= "Thank you for staying at {$this->hotelName}!\n\n";

            $message .= "📌 <b>Booking Reference:</b> #{$ref}\n";
            $message .= "🛏️ <b>Room:</b> {$booking->room->name}\n";
            $message .= "⏰ <b>Check-out Time:</b> " . now()->format('H:i') . "\n\n";

            $message .= "📧 Your check-out confirmation has been sent to your email.\n\n";

            $message .= "⭐ <b>Please Rate Your Stay</b>\n";
            $message .= "Your feedback helps us improve our services.\n";

            $buttons = [
                [
                    ['text' => '⭐ Leave Review', 'callback_data' => "booking_detail_review_{$bookingId}"],
                ],
                [
                    ['text' => '🏠 Home', 'callback_data' => 'menu_main'],
                    ['text' => '📚 My Bookings', 'callback_data' => 'menu_bookings'],
                ],
            ];

            Log::info('Express check-out completed', [
                'booking_id' => $booking->id,
                'user_id' => $this->userId,
            ]);

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling express check-out', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing check-out.',
            ];
        }
    }

    /**
     * Handle payment via Telegram
     */
    public function handlePaymentRequest(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            $paymentCommand = new PaymentCommand($this->chatId, $this->userId, app('App\Payments\Gateways\ChapaGateway'));

            return $paymentCommand->showPaymentOptions();
        } catch (\Exception $e) {
            Log::error('Error handling payment request', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing payment.',
            ];
        }
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
     * Set hotel name
     */
    public function setHotelName(string $name): self
    {
        $this->hotelName = $name;
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
     * Set check-out time
     */
    public function setCheckOutTime(string $time): self
    {
        $this->checkOutTime = $time;
        return $this;
    }
}
