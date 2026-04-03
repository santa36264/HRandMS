<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CancellationCommand
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
     * Cancellation deadline (hours before check-in)
     */
    private const CANCELLATION_DEADLINE_HOURS = 24;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Get cancellation policy and refund info
     */
    public function getCancellationPolicy(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            if (!$booking->isCancellable()) {
                return [
                    'success' => false,
                    'message' => '❌ This booking cannot be cancelled.',
                ];
            }

            $policy = $this->calculateCancellationPolicy($booking);

            return [
                'success' => true,
                'message' => $this->formatCancellationMessage($booking, $policy),
                'buttons' => $this->getCancellationButtons($booking),
                'policy' => $policy,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting cancellation policy', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Calculate cancellation policy
     */
    private function calculateCancellationPolicy(Booking $booking): array
    {
        $now = now();
        $checkInDate = $booking->check_in_date;
        $cancellationDeadline = $checkInDate->copy()->subHours(self::CANCELLATION_DEADLINE_HOURS);
        $isWithinDeadline = $now->lessThan($cancellationDeadline);

        $refundAmount = 0;
        $refundPercentage = 0;
        $refundEligible = false;

        if ($isWithinDeadline) {
            $refundAmount = $booking->total_price;
            $refundPercentage = 100;
            $refundEligible = true;
        }

        return [
            'is_within_deadline' => $isWithinDeadline,
            'cancellation_deadline' => $cancellationDeadline,
            'hours_until_deadline' => $now->diffInHours($cancellationDeadline, false),
            'refund_eligible' => $refundEligible,
            'refund_amount' => $refundAmount,
            'refund_percentage' => $refundPercentage,
            'original_amount' => $booking->total_price,
        ];
    }

    /**
     * Format cancellation message
     */
    private function formatCancellationMessage(Booking $booking, array $policy): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));
        $nights = $booking->check_out_date->diffInDays($booking->check_in_date);

        $message = "⚠️ <b>CANCEL BOOKING</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Booking Details
        $message .= "📌 <b>Booking Reference:</b> #{$ref}\n";
        $message .= "🛏️ <b>Room:</b> {$booking->room->type}\n";
        $message .= "📅 <b>Dates:</b> {$booking->check_in_date->format('M d')} - {$booking->check_out_date->format('M d, Y')} ({$nights}n)\n";
        $message .= "💰 <b>Total Amount:</b> {$booking->total_price} ETB\n\n";

        // Cancellation Policy
        $message .= "📋 <b>Cancellation Policy:</b>\n";
        $message .= "   • Free cancellation up to 24 hours before check-in\n";
        $message .= "   • Deadline: {$policy['cancellation_deadline']->format('M d, Y H:i')}\n\n";

        // Refund Information
        if ($policy['refund_eligible']) {
            $message .= "✅ <b>REFUND ELIGIBLE</b>\n";
            $message .= "   • Refund Amount: <b>{$policy['refund_amount']} ETB</b> (100%)\n";
            $message .= "   • Processing Time: 3-5 business days\n";
            $message .= "   • Method: Original payment method\n\n";
        } else {
            $message .= "❌ <b>NON-REFUNDABLE</b>\n";
            $message .= "   • Cancellation deadline has passed\n";
            $message .= "   • No refund will be issued\n";
            $message .= "   • Booking will be marked as cancelled\n\n";
        }

        // Warning
        $message .= "⚠️ <b>Important:</b>\n";
        $message .= "   • This action cannot be undone\n";
        $message .= "   • You will receive a cancellation confirmation\n";
        $message .= "   • Hotel will be notified immediately\n\n";

        $message .= "Are you sure you want to cancel this booking?";

        return $message;
    }

    /**
     * Get cancellation buttons
     */
    private function getCancellationButtons(Booking $booking): array
    {
        return [
            [
                ['text' => '✅ Confirm Cancel', 'callback_data' => "cancel_confirm_{$booking->id}"],
                ['text' => '🔙 Keep Booking', 'callback_data' => "mybooking_view_{$booking->id}"],
            ],
        ];
    }

    /**
     * Process cancellation
     */
    public function processCancellation(int $bookingId): array
    {
        try {
            $booking = Booking::with(['user', 'room', 'payments'])->where('user_id', $this->userId)->findOrFail($bookingId);

            if (!$booking->isCancellable()) {
                return [
                    'success' => false,
                    'message' => '❌ This booking cannot be cancelled.',
                ];
            }

            // Calculate refund
            $policy = $this->calculateCancellationPolicy($booking);
            $cancellationRef = $this->generateCancellationReference();

            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reference' => $cancellationRef,
                'cancelled_at' => now(),
            ]);

            // Process refund if applicable
            if ($policy['refund_eligible']) {
                $this->processRefund($booking, $policy, $cancellationRef);
            }

            // Release room availability
            $this->releaseRoomAvailability($booking);

            // Send notifications
            $this->sendCancellationNotifications($booking, $policy, $cancellationRef);

            return [
                'success' => true,
                'message' => $this->formatCancellationConfirmation($booking, $policy, $cancellationRef),
                'buttons' => [[
                    ['text' => '📚 My Bookings', 'callback_data' => 'menu_bookings'],
                    ['text' => '🏠 Home', 'callback_data' => 'menu_main'],
                ]],
                'cancellation_ref' => $cancellationRef,
            ];
        } catch (\Exception $e) {
            Log::error('Error processing cancellation', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error processing cancellation. Please try again.',
            ];
        }
    }

    /**
     * Generate cancellation reference
     */
    private function generateCancellationReference(): string
    {
        return 'CAN-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /**
     * Process refund
     */
    private function processRefund(Booking $booking, array $policy, string $cancellationRef): void
    {
        try {
            $payment = $booking->payments()->where('status', 'completed')->latest()->first();

            if (!$payment) {
                Log::warning('No completed payment found for refund', ['booking_id' => $booking->id]);
                return;
            }

            // Create refund record
            $refund = new \App\Models\Refund();
            $refund->payment_id = $payment->id;
            $refund->booking_id = $booking->id;
            $refund->user_id = $booking->user_id;
            $refund->amount = $policy['refund_amount'];
            $refund->percentage = $policy['refund_percentage'];
            $refund->reason = 'User cancellation';
            $refund->cancellation_reference = $cancellationRef;
            $refund->status = 'pending';
            $refund->save();

            Log::info('Refund processed', [
                'booking_id' => $booking->id,
                'amount' => $policy['refund_amount'],
                'cancellation_ref' => $cancellationRef,
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing refund', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Release room availability
     */
    private function releaseRoomAvailability(Booking $booking): void
    {
        try {
            // Mark room as available for the cancelled dates
            $roomAvailability = \App\Models\RoomAvailability::where('room_id', $booking->room_id)
                ->whereBetween('date', [$booking->check_in_date, $booking->check_out_date->subDay()])
                ->update(['is_available' => true]);

            Log::info('Room availability released', [
                'room_id' => $booking->room_id,
                'dates_released' => $roomAvailability,
            ]);
        } catch (\Exception $e) {
            Log::error('Error releasing room availability', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send cancellation notifications
     */
    private function sendCancellationNotifications(Booking $booking, array $policy, string $cancellationRef): void
    {
        try {
            // Send user notification
            $this->sendUserCancellationNotification($booking, $policy, $cancellationRef);

            // Notify hotel staff
            $this->notifyHotelStaff($booking, $policy, $cancellationRef);
        } catch (\Exception $e) {
            Log::error('Error sending cancellation notifications', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send user cancellation notification
     */
    private function sendUserCancellationNotification(Booking $booking, array $policy, string $cancellationRef): void
    {
        try {
            $booking->user->notify(new \App\Notifications\BookingCancelledNotification($booking, $policy, $cancellationRef));
            Log::info('User cancellation notification sent', ['user_id' => $booking->user_id]);
        } catch (\Exception $e) {
            Log::error('Error sending user cancellation notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notify hotel staff
     */
    private function notifyHotelStaff(Booking $booking, array $policy, string $cancellationRef): void
    {
        try {
            // Log for hotel staff notification system
            Log::info('Hotel staff notification: Booking cancelled', [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'cancellation_reference' => $cancellationRef,
                'room_id' => $booking->room_id,
                'check_in_date' => $booking->check_in_date,
                'check_out_date' => $booking->check_out_date,
                'refund_eligible' => $policy['refund_eligible'],
                'refund_amount' => $policy['refund_amount'],
                'user_name' => $booking->user->name,
                'user_email' => $booking->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying hotel staff', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Format cancellation confirmation
     */
    private function formatCancellationConfirmation(Booking $booking, array $policy, string $cancellationRef): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));

        $message = "✅ <b>BOOKING CANCELLED</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $message .= "📌 <b>Original Booking:</b> #{$ref}\n";
        $message .= "🔄 <b>Cancellation Reference:</b> {$cancellationRef}\n\n";

        $message .= "🛏️ <b>Room:</b> {$booking->room->type}\n";
        $message .= "📅 <b>Dates:</b> {$booking->check_in_date->format('M d')} - {$booking->check_out_date->format('M d, Y')}\n\n";

        if ($policy['refund_eligible']) {
            $message .= "💰 <b>Refund Details:</b>\n";
            $message .= "   • Amount: <b>{$policy['refund_amount']} ETB</b>\n";
            $message .= "   • Status: Processing\n";
            $message .= "   • Timeline: 3-5 business days\n";
            $message .= "   • Method: Original payment method\n\n";
        } else {
            $message .= "❌ <b>No Refund:</b>\n";
            $message .= "   • Cancellation deadline has passed\n";
            $message .= "   • Non-refundable booking\n\n";
        }

        $message .= "📧 <b>Confirmation Email:</b>\n";
        $message .= "   A detailed cancellation confirmation has been sent to your email.\n\n";

        $message .= "🏨 <b>Hotel Notification:</b>\n";
        $message .= "   The hotel has been notified of your cancellation.\n\n";

        $message .= "❓ <b>Need Help?</b>\n";
        $message .= "   Contact us at info@sataabhotel.com\n";
        $message .= "   Phone: +251911234567\n";

        return $message;
    }

    /**
     * Get cancellation history
     */
    public function getCancellationHistory(): array
    {
        try {
            $cancelledBookings = Booking::where('user_id', $this->userId)
                ->where('status', 'cancelled')
                ->with(['room', 'payments'])
                ->orderBy('cancelled_at', 'desc')
                ->limit(10)
                ->get();

            if ($cancelledBookings->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "📭 <b>Cancellation History</b>\n\n"
                        . "You haven't cancelled any bookings.",
                ];
            }

            $message = "📋 <b>Cancellation History</b>\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            foreach ($cancelledBookings as $booking) {
                $ref = strtoupper(substr(md5($booking->id), 0, 8));
                $message .= "❌ #{$ref}\n";
                $message .= "   Room: {$booking->room->type}\n";
                $message .= "   Cancelled: {$booking->cancelled_at->format('M d, Y')}\n";
                $message .= "   Original: {$booking->total_price} ETB\n\n";
            }

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting cancellation history', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading cancellation history.',
            ];
        }
    }
}
