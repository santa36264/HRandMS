<?php

namespace App\Telegram\HotelBookingBot\Formatters;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class BookingDetailsFormatter
{
    /**
     * Booking model
     */
    private Booking $booking;

    /**
     * Payment model
     */
    private ?Payment $payment;

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
     * Check-out time
     */
    private string $checkOutTime = '11:00';

    /**
     * Constructor
     */
    public function __construct(Booking $booking, ?Payment $payment = null)
    {
        $this->booking = $booking;
        $this->payment = $payment;
    }

    /**
     * Get formatted detailed view
     */
    public function getFormattedDetails(): string
    {
        $guestNames = json_decode($this->booking->guest_names, true) ?? [];
        $nights = $this->booking->check_out_date->diffInDays($this->booking->check_in_date);
        $ref = strtoupper(substr(md5($this->booking->id), 0, 8));
        $statusEmoji = $this->getStatusEmoji();

        $message = "🎫 <b>BOOKING DETAILS</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Booking Reference & Status
        $message .= "📌 <b>Reference:</b> #{$ref}\n";
        $message .= "{$statusEmoji} <b>Status:</b> " . $this->formatStatus() . "\n\n";

        // Room Information
        $message .= "🛏️ <b>Room Details:</b>\n";
        $message .= "   Type: {$this->booking->room->type}\n";
        $message .= "   Name: {$this->booking->room->name}\n";
        $message .= "   Capacity: {$this->booking->room->capacity} guests\n\n";

        // Stay Details
        $message .= "📅 <b>Stay Details:</b>\n";
        $message .= "   Check-in: {$this->booking->check_in_date->format('M d, Y')} ({$this->checkInTime})\n";
        $message .= "   Check-out: {$this->booking->check_out_date->format('M d, Y')} ({$this->checkOutTime})\n";
        $message .= "   Duration: {$nights} night" . ($nights > 1 ? 's' : '') . "\n\n";

        // Guest Information
        $message .= "👥 <b>Guests (" . count($guestNames) . "):</b>\n";
        foreach ($guestNames as $index => $name) {
            $message .= "   " . ($index + 1) . ". {$name}\n";
        }
        $message .= "\n";

        // Special Requests
        if (!empty($this->booking->special_requests)) {
            $message .= "📝 <b>Special Requests:</b>\n";
            $message .= "   {$this->booking->special_requests}\n\n";
        }

        // Payment Information
        $message .= "💰 <b>Payment Details:</b>\n";
        $message .= "   Total: <b>{$this->booking->total_price} ETB</b>\n";
        if ($this->payment) {
            $message .= "   Method: " . ucfirst($this->payment->gateway) . "\n";
            $message .= "   Transaction: {$this->payment->transaction_ref}\n";
            $message .= "   Status: " . ucfirst($this->payment->status) . "\n";
        }
        $message .= "\n";

        // Cancellation Policy
        $message .= $this->getCancellationPolicySection();

        // Refund Information
        $message .= $this->getRefundInformationSection();

        // Hotel Contact
        $message .= "📞 <b>Hotel Contact:</b>\n";
        $message .= "   Phone: {$this->hotelPhone}\n";
        $message .= "   Email: info@sataabhotel.com\n";
        $message .= "   Address: {$this->hotelAddress}\n";

        return $message;
    }

    /**
     * Get cancellation policy section
     */
    private function getCancellationPolicySection(): string
    {
        $cancellationDeadline = $this->booking->check_in_date->copy()->subHours(24);
        $daysUntilDeadline = now()->diffInDays($cancellationDeadline, false);
        $canCancel = $this->booking->isCancellable();

        $section = "📋 <b>Cancellation Policy:</b>\n";
        $section .= "   • Free cancellation up to 24 hours before check-in\n";
        $section .= "   • Cancellation deadline: {$cancellationDeadline->format('M d, Y H:i')}\n";

        if ($daysUntilDeadline > 0) {
            $section .= "   • ✅ You can still cancel for free\n";
        } elseif ($daysUntilDeadline == 0) {
            $section .= "   • ⚠️ Cancellation deadline is today\n";
        } else {
            $section .= "   • ❌ Cancellation deadline has passed\n";
        }

        $section .= "\n";
        return $section;
    }

    /**
     * Get refund information section
     */
    private function getRefundInformationSection(): string
    {
        $section = "💵 <b>Refund Eligibility:</b>\n";

        if (!$this->payment || $this->payment->status !== 'completed') {
            $section .= "   • No payment received yet\n\n";
            return $section;
        }

        $cancellationDeadline = $this->booking->check_in_date->copy()->subHours(24);
        $isWithinDeadline = now()->lessThan($cancellationDeadline);

        if ($isWithinDeadline) {
            $section .= "   • ✅ Full refund eligible\n";
            $section .= "   • Amount: {$this->booking->total_price} ETB\n";
            $section .= "   • Processing time: 3-5 business days\n";
        } else {
            $section .= "   • ❌ Non-refundable (deadline passed)\n";
            $section .= "   • Cancellation policy applies\n";
        }

        $section .= "\n";
        return $section;
    }

    /**
     * Get status emoji
     */
    private function getStatusEmoji(): string
    {
        return match ($this->booking->status) {
            'confirmed' => '📌',
            'checked_in' => '🏠',
            'checked_out', 'completed' => '✅',
            'cancelled' => '❌',
            'pending_payment' => '⏳',
            'payment_failed' => '❌',
            default => '📌',
        };
    }

    /**
     * Format status with description
     */
    private function formatStatus(): string
    {
        $status = ucfirst(str_replace('_', ' ', $this->booking->status));
        $today = Carbon::today();

        if ($this->booking->status === 'confirmed') {
            if ($this->booking->check_in_date->isToday()) {
                return "$status (Check-in Today)";
            } elseif ($this->booking->check_in_date->isFuture()) {
                $daysUntil = now()->diffInDays($this->booking->check_in_date);
                return "$status (in $daysUntil days)";
            }
        } elseif ($this->booking->status === 'checked_in') {
            $daysStayed = $this->booking->check_in_date->diffInDays(now());
            return "$status (Day $daysStayed)";
        }

        return $status;
    }

    /**
     * Get action buttons based on booking status
     */
    public function getActionButtons(): array
    {
        $today = Carbon::today();
        $buttons = [];

        if ($this->booking->status === 'confirmed') {
            if ($this->booking->check_in_date->isFuture()) {
                // Upcoming booking
                $buttons[] = [
                    ['text' => '❌ Cancel Booking', 'callback_data' => "booking_detail_cancel_{$this->booking->id}"],
                ];
                $buttons[] = [
                    ['text' => '📅 Modify Dates', 'callback_data' => "booking_detail_modify_{$this->booking->id}"],
                    ['text' => '➕ Add Services', 'callback_data' => "booking_detail_services_{$this->booking->id}"],
                ];
            } elseif ($this->booking->check_in_date->isToday()) {
                // Check-in today
                $buttons[] = [
                    ['text' => '🔑 Check-in QR', 'callback_data' => "booking_detail_qr_{$this->booking->id}"],
                    ['text' => '📍 Directions', 'callback_data' => "booking_detail_directions_{$this->booking->id}"],
                ];
            }
        } elseif ($this->booking->status === 'checked_in') {
            // Active stay
            $buttons[] = [
                ['text' => '🔧 Request Service', 'callback_data' => "booking_detail_service_{$this->booking->id}"],
                ['text' => '📅 Extend Stay', 'callback_data' => "booking_detail_extend_{$this->booking->id}"],
            ];
        } elseif ($this->booking->status === 'checked_out' || $this->booking->status === 'completed') {
            // Completed booking
            $buttons[] = [
                ['text' => '⭐ Leave Review', 'callback_data' => "booking_detail_review_{$this->booking->id}"],
                ['text' => '🔄 Book Again', 'callback_data' => "booking_detail_rebook_{$this->booking->id}"],
            ];
        }

        // Common buttons
        $buttons[] = [
            ['text' => '🧾 View Receipt', 'callback_data' => "booking_detail_receipt_{$this->booking->id}"],
            ['text' => '🔙 Back', 'callback_data' => 'menu_bookings'],
        ];

        return $buttons;
    }

    /**
     * Get cancellation confirmation message
     */
    public function getCancellationConfirmation(): string
    {
        $ref = strtoupper(substr(md5($this->booking->id), 0, 8));
        $cancellationDeadline = $this->booking->check_in_date->copy()->subHours(24);
        $isWithinDeadline = now()->lessThan($cancellationDeadline);

        $message = "⚠️ <b>Cancel Booking?</b>\n\n";
        $message .= "Booking #{$ref}\n";
        $message .= "{$this->booking->room->type} - {$this->booking->check_in_date->format('M d, Y')}\n\n";

        if ($isWithinDeadline) {
            $message .= "✅ <b>Full refund eligible</b>\n";
            $message .= "Amount: {$this->booking->total_price} ETB\n";
            $message .= "Processing: 3-5 business days\n\n";
        } else {
            $message .= "❌ <b>Non-refundable</b>\n";
            $message .= "Cancellation deadline has passed\n\n";
        }

        $message .= "Are you sure you want to cancel?";

        return $message;
    }

    /**
     * Get modify dates message
     */
    public function getModifyDatesMessage(): string
    {
        $ref = strtoupper(substr(md5($this->booking->id), 0, 8));

        return "📅 <b>Modify Booking Dates</b>\n\n"
            . "Booking #{$ref}\n"
            . "Current dates: {$this->booking->check_in_date->format('M d')} - {$this->booking->check_out_date->format('M d, Y')}\n\n"
            . "Please enter new check-in date (YYYY-MM-DD):";
    }

    /**
     * Get check-in QR message
     */
    public function getCheckInQRMessage(): string
    {
        $ref = strtoupper(substr(md5($this->booking->id), 0, 8));

        return "🔑 <b>Check-in QR Code</b>\n\n"
            . "Booking #{$ref}\n"
            . "Room: {$this->booking->room->name}\n"
            . "Guest Count: {$this->booking->guest_count}\n\n"
            . "Show this QR code at the front desk to check in.";
    }

    /**
     * Get directions message
     */
    public function getDirectionsMessage(): string
    {
        return "📍 <b>Hotel Directions</b>\n\n"
            . "Hotel: {$this->hotelName}\n"
            . "Address: {$this->hotelAddress}\n"
            . "Phone: {$this->hotelPhone}\n\n"
            . "Click the button below to open directions in Google Maps.";
    }

    /**
     * Get request service message
     */
    public function getRequestServiceMessage(): string
    {
        return "🔧 <b>Request Service</b>\n\n"
            . "What service do you need?\n\n"
            . "Available services:\n"
            . "• Room Cleaning\n"
            . "• Extra Towels\n"
            . "• Room Service\n"
            . "• Maintenance\n"
            . "• Other\n\n"
            . "Please select or describe your request:";
    }

    /**
     * Get extend stay message
     */
    public function getExtendStayMessage(): string
    {
        $currentCheckOut = $this->booking->check_out_date->format('M d, Y');

        return "📅 <b>Extend Your Stay</b>\n\n"
            . "Current check-out: {$currentCheckOut}\n"
            . "Room: {$this->booking->room->name}\n\n"
            . "How many additional nights would you like?";
    }

    /**
     * Get review message
     */
    public function getReviewMessage(): string
    {
        $ref = strtoupper(substr(md5($this->booking->id), 0, 8));

        return "⭐ <b>Leave a Review</b>\n\n"
            . "Booking #{$ref}\n"
            . "Room: {$this->booking->room->name}\n"
            . "Dates: {$this->booking->check_in_date->format('M d')} - {$this->booking->check_out_date->format('M d, Y')}\n\n"
            . "Please rate your experience (1-5 stars) and share your feedback:";
    }

    /**
     * Get rebook message
     */
    public function getRebookMessage(): string
    {
        return "🔄 <b>Book Again</b>\n\n"
            . "Would you like to book the same room for another stay?\n\n"
            . "Room: {$this->booking->room->name}\n"
            . "Previous stay: {$this->booking->check_in_date->format('M d')} - {$this->booking->check_out_date->format('M d, Y')}\n\n"
            . "Click the button below to search for available dates.";
    }

    /**
     * Get QR code URL
     */
    public function getQRCodeUrl(): string
    {
        $data = json_encode([
            'booking_reference' => strtoupper(substr(md5($this->booking->id), 0, 8)),
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'check_in_date' => $this->booking->check_in_date->format('Y-m-d'),
            'guest_count' => $this->booking->guest_count,
            'hotel' => $this->hotelName,
        ]);

        return "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($data);
    }

    /**
     * Get directions URL
     */
    public function getDirectionsUrl(): string
    {
        return "https://maps.google.com/?q=" . urlencode($this->hotelAddress);
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
}
