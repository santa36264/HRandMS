<?php

namespace App\Telegram\HotelBookingBot\Formatters;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;

class DigitalReceiptFormatter
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
     * Get formatted receipt message
     */
    public function getFormattedReceipt(): string
    {
        $guestNames = json_decode($this->booking->guest_names, true) ?? [];
        $nights = $this->booking->check_out_date->diffInDays($this->booking->check_in_date);

        $receipt = "🎫 <b>BOOKING CONFIRMATION</b>\n";
        $receipt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Booking Reference
        $receipt .= "🎫 <b>Booking Reference:</b> #" . strtoupper(substr(md5($this->booking->id), 0, 8)) . "\n";
        
        // Hotel Info
        $receipt .= "🏨 <b>Hotel:</b> {$this->hotelName}\n";
        $receipt .= "📍 <b>Address:</b> {$this->hotelAddress}\n\n";

        // Dates
        $receipt .= "📅 <b>Stay Dates:</b>\n";
        $receipt .= "   Check-in: {$this->booking->check_in_date->format('M d, Y')} ({$this->checkInTime})\n";
        $receipt .= "   Check-out: {$this->booking->check_out_date->format('M d, Y')} ({$this->checkOutTime})\n";
        $receipt .= "   Duration: <b>{$nights} night" . ($nights > 1 ? 's' : '') . "</b>\n\n";

        // Room Info
        $receipt .= "🛏️ <b>Room Details:</b>\n";
        $receipt .= "   Type: {$this->booking->room->type}\n";
        $receipt .= "   Name: {$this->booking->room->name}\n";
        $receipt .= "   Capacity: {$this->booking->room->capacity} guests\n\n";

        // Guest Information
        $receipt .= "👥 <b>Guest Information:</b>\n";
        foreach ($guestNames as $index => $name) {
            $receipt .= "   " . ($index + 1) . ". {$name}\n";
        }
        $receipt .= "\n";

        // Special Requests
        if (!empty($this->booking->special_requests)) {
            $receipt .= "📝 <b>Special Requests:</b>\n";
            $receipt .= "   {$this->booking->special_requests}\n\n";
        }

        // Payment Information
        $receipt .= "💰 <b>Payment Details:</b>\n";
        $receipt .= "   Amount: <b>{$this->booking->total_price} ETB</b>\n";
        if ($this->payment) {
            $receipt .= "   Method: " . ucfirst($this->payment->gateway) . "\n";
            $receipt .= "   Transaction ID: {$this->payment->transaction_ref}\n";
            $receipt .= "   Status: ✅ Paid\n";
        }
        $receipt .= "\n";

        // Contact Information
        $receipt .= "📞 <b>Hotel Contact:</b>\n";
        $receipt .= "   Phone: {$this->hotelPhone}\n";
        $receipt .= "   Email: info@sataabhotel.com\n\n";

        // Important Notes
        $receipt .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $receipt .= "✅ <b>Confirmation Details:</b>\n";
        $receipt .= "• A confirmation email has been sent\n";
        $receipt .= "• Please arrive 15 minutes before check-in\n";
        $receipt .= "• Cancellation policy applies\n";
        $receipt .= "• For changes, contact the hotel\n\n";

        $receipt .= "Thank you for choosing {$this->hotelName}! 🏨\n";

        return $receipt;
    }

    /**
     * Get receipt buttons
     */
    public function getReceiptButtons(): array
    {
        $mapsUrl = "https://maps.google.com/?q=" . urlencode($this->hotelAddress);
        $phoneUrl = "tel:" . str_replace([' ', '-', '+'], '', $this->hotelPhone);
        $calendarUrl = $this->generateCalendarUrl();

        return [
            [
                ['text' => '📍 Get Directions', 'url' => $mapsUrl],
                ['text' => '📞 Call Hotel', 'url' => $phoneUrl],
            ],
            [
                ['text' => '📅 Add to Calendar', 'url' => $calendarUrl],
            ],
            [
                ['text' => '🔁 Modify Booking', 'callback_data' => 'booking_modify'],
                ['text' => '❓ Need Help?', 'callback_data' => 'menu_help'],
            ],
        ];
    }

    /**
     * Generate calendar URL (Google Calendar)
     */
    private function generateCalendarUrl(): string
    {
        $title = urlencode("Check-in at {$this->hotelName}");
        $description = urlencode("Room: {$this->booking->room->name}\nGuests: " . implode(", ", json_decode($this->booking->guest_names, true) ?? []));
        $location = urlencode($this->hotelAddress);
        $startTime = $this->booking->check_in_date->format('Ymd') . 'T' . str_replace(':', '', $this->checkInTime) . '00Z';
        $endTime = $this->booking->check_out_date->format('Ymd') . 'T' . str_replace(':', '', $this->checkOutTime) . '00Z';

        return "https://calendar.google.com/calendar/render?action=TEMPLATE"
            . "&text={$title}"
            . "&details={$description}"
            . "&location={$location}"
            . "&dates={$startTime}/{$endTime}";
    }

    /**
     * Generate QR code data for check-in
     */
    public function getQRCodeData(): string
    {
        $bookingRef = strtoupper(substr(md5($this->booking->id), 0, 8));
        
        return json_encode([
            'booking_reference' => $bookingRef,
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'check_in_date' => $this->booking->check_in_date->format('Y-m-d'),
            'guest_count' => $this->booking->guest_count,
            'hotel' => $this->hotelName,
        ]);
    }

    /**
     * Get QR code URL (using QR code API)
     */
    public function getQRCodeUrl(): string
    {
        $data = $this->getQRCodeData();
        $encoded = urlencode($data);
        
        // Using qr-server.com for QR code generation
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encoded}";
    }

    /**
     * Get booking summary for email
     */
    public function getEmailSummary(): array
    {
        $guestNames = json_decode($this->booking->guest_names, true) ?? [];
        $nights = $this->booking->check_out_date->diffInDays($this->booking->check_in_date);
        $bookingRef = strtoupper(substr(md5($this->booking->id), 0, 8));

        return [
            'booking_reference' => $bookingRef,
            'hotel_name' => $this->hotelName,
            'hotel_address' => $this->hotelAddress,
            'hotel_phone' => $this->hotelPhone,
            'check_in_date' => $this->booking->check_in_date->format('M d, Y'),
            'check_out_date' => $this->booking->check_out_date->format('M d, Y'),
            'check_in_time' => $this->checkInTime,
            'check_out_time' => $this->checkOutTime,
            'nights' => $nights,
            'room_type' => $this->booking->room->type,
            'room_name' => $this->booking->room->name,
            'room_capacity' => $this->booking->room->capacity,
            'guest_names' => $guestNames,
            'guest_count' => $this->booking->guest_count,
            'special_requests' => $this->booking->special_requests,
            'total_price' => $this->booking->total_price,
            'payment_method' => $this->payment ? ucfirst($this->payment->gateway) : 'N/A',
            'transaction_id' => $this->payment ? $this->payment->transaction_ref : 'N/A',
            'qr_code_url' => $this->getQRCodeUrl(),
            'maps_url' => "https://maps.google.com/?q=" . urlencode($this->hotelAddress),
            'phone_url' => "tel:" . str_replace([' ', '-', '+'], '', $this->hotelPhone),
        ];
    }

    /**
     * Get iCalendar format for calendar file
     */
    public function getICalendarFormat(): string
    {
        $bookingRef = strtoupper(substr(md5($this->booking->id), 0, 8));
        $guestNames = json_decode($this->booking->guest_names, true) ?? [];
        
        $startDate = $this->booking->check_in_date->format('Ymd');
        $endDate = $this->booking->check_out_date->format('Ymd');
        $timestamp = now()->format('Ymd\THis\Z');

        $ical = "BEGIN:VCALENDAR\n";
        $ical .= "VERSION:2.0\n";
        $ical .= "PRODID:-//SATAAB Hotel//Booking Confirmation//EN\n";
        $ical .= "CALSCALE:GREGORIAN\n";
        $ical .= "METHOD:PUBLISH\n";
        $ical .= "BEGIN:VEVENT\n";
        $ical .= "UID:{$bookingRef}@sataabhotel.com\n";
        $ical .= "DTSTAMP:{$timestamp}\n";
        $ical .= "DTSTART;VALUE=DATE:{$startDate}\n";
        $ical .= "DTEND;VALUE=DATE:{$endDate}\n";
        $ical .= "SUMMARY:Check-in at {$this->hotelName}\n";
        $ical .= "DESCRIPTION:Room: {$this->booking->room->name}\\nGuests: " . implode(", ", $guestNames) . "\n";
        $ical .= "LOCATION:{$this->hotelAddress}\n";
        $ical .= "ORGANIZER;CN={$this->hotelName}:mailto:info@sataabhotel.com\n";
        $ical .= "SEQUENCE:0\n";
        $ical .= "STATUS:CONFIRMED\n";
        $ical .= "TRANSP:OPAQUE\n";
        $ical .= "END:VEVENT\n";
        $ical .= "END:VCALENDAR\n";

        return $ical;
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

    /**
     * Set check-out time
     */
    public function setCheckOutTime(string $time): self
    {
        $this->checkOutTime = $time;
        return $this;
    }
}
