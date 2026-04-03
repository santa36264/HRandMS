<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MyBookingsCommand
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
     * Items per page
     */
    private const ITEMS_PER_PAGE = 5;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show bookings
     */
    public function showBookings(int $page = 1): array
    {
        try {
            $user = User::find($this->userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '❌ User not found.',
                ];
            }

            $bookings = $user->bookings()
                ->with('room')
                ->orderBy('check_in_date', 'desc')
                ->get();

            if ($bookings->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "📭 <b>My Bookings</b>\n\n"
                        . "You don't have any bookings yet.\n\n"
                        . "Start exploring and book your stay at SATAAB Hotel!",
                    'buttons' => [[
                        ['text' => '🔍 Search Rooms', 'callback_data' => 'menu_search'],
                    ]],
                ];
            }

            // Group bookings by status
            $grouped = $this->groupBookingsByStatus($bookings);

            // Get paginated results
            $allBookings = [];
            foreach (['upcoming', 'current', 'completed', 'cancelled'] as $status) {
                $allBookings = array_merge($allBookings, $grouped[$status] ?? []);
            }

            $totalPages = ceil(count($allBookings) / self::ITEMS_PER_PAGE);
            $page = max(1, min($page, $totalPages));

            $paginatedBookings = array_slice(
                $allBookings,
                ($page - 1) * self::ITEMS_PER_PAGE,
                self::ITEMS_PER_PAGE
            );

            $message = $this->formatBookingsList($paginatedBookings, $grouped);
            $buttons = $this->getBookingsButtons($paginatedBookings, $page, $totalPages);

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
                'page' => $page,
                'total_pages' => $totalPages,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing bookings', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading bookings. Please try again.',
            ];
        }
    }

    /**
     * Group bookings by status
     */
    private function groupBookingsByStatus($bookings): array
    {
        $grouped = [
            'upcoming' => [],
            'current' => [],
            'completed' => [],
            'cancelled' => [],
        ];

        $today = Carbon::today();

        foreach ($bookings as $booking) {
            if ($booking->status === 'cancelled') {
                $grouped['cancelled'][] = $booking;
            } elseif ($booking->check_in_date > $today) {
                $grouped['upcoming'][] = $booking;
            } elseif ($booking->check_in_date <= $today && $booking->check_out_date >= $today) {
                $grouped['current'][] = $booking;
            } else {
                $grouped['completed'][] = $booking;
            }
        }

        return $grouped;
    }

    /**
     * Format bookings list
     */
    private function formatBookingsList(array $bookings, array $grouped): string
    {
        $message = "📚 <b>My Bookings</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Show status summary
        $message .= "📌 <b>Upcoming:</b> " . count($grouped['upcoming']) . " | ";
        $message .= "🏠 <b>Current:</b> " . count($grouped['current']) . " | ";
        $message .= "✅ <b>Completed:</b> " . count($grouped['completed']) . " | ";
        $message .= "❌ <b>Cancelled:</b> " . count($grouped['cancelled']) . "\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($bookings as $booking) {
            $message .= $this->formatBookingCard($booking);
        }

        return $message;
    }

    /**
     * Format single booking card
     */
    private function formatBookingCard(Booking $booking): string
    {
        $ref = strtoupper(substr(md5($booking->id), 0, 8));
        $statusEmoji = $this->getStatusEmoji($booking);
        $nights = $booking->check_out_date->diffInDays($booking->check_in_date);

        $card = "🎫 <b>Booking #{$ref}</b>\n";
        $card .= "🛏️ Room: {$booking->room->type}\n";
        $card .= "📅 {$booking->check_in_date->format('M d')} - {$booking->check_out_date->format('M d, Y')} ({$nights}n)\n";
        $card .= "{$statusEmoji} Status: " . ucfirst(str_replace('_', ' ', $booking->status)) . "\n";
        $card .= "💰 Total: {$booking->total_price} ETB\n";
        $card .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        return $card;
    }

    /**
     * Get status emoji
     */
    private function getStatusEmoji(Booking $booking): string
    {
        return match ($booking->status) {
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
     * Get bookings buttons
     */
    private function getBookingsButtons(array $bookings, int $page, int $totalPages): array
    {
        $buttons = [];

        // Add view details buttons for each booking
        foreach ($bookings as $booking) {
            $ref = strtoupper(substr(md5($booking->id), 0, 8));
            $buttons[] = [
                ['text' => "📖 View #{$ref}", 'callback_data' => "mybooking_view_{$booking->id}"],
            ];
        }

        // Add pagination buttons
        if ($totalPages > 1) {
            $paginationRow = [];
            if ($page > 1) {
                $paginationRow[] = ['text' => '⬅️ Previous', 'callback_data' => "mybookings_page_" . ($page - 1)];
            }
            $paginationRow[] = ['text' => "Page {$page}/{$totalPages}", 'callback_data' => 'mybookings_page_info'];
            if ($page < $totalPages) {
                $paginationRow[] = ['text' => 'Next ➡️', 'callback_data' => "mybookings_page_" . ($page + 1)];
            }
            $buttons[] = $paginationRow;
        }

        // Add filter and action buttons
        $buttons[] = [
            ['text' => '📅 Filter by Date', 'callback_data' => 'mybookings_filter'],
            ['text' => '🔙 Back to Menu', 'callback_data' => 'menu_main'],
        ];

        return $buttons;
    }

    /**
     * Show booking details
     */
    public function showBookingDetails(int $bookingId): array
    {
        try {
            $booking = Booking::with(['room', 'payments'])
                ->where('user_id', $this->userId)
                ->findOrFail($bookingId);

            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            $message = $formatter->getFormattedDetails();
            $buttons = $formatter->getActionButtons();

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
                'booking_id' => $booking->id,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing booking details', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get cancellation confirmation
     */
    public function getCancellationConfirmation(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            $message = $formatter->getCancellationConfirmation();
            $buttons = [
                [
                    ['text' => '✅ Confirm Cancel', 'callback_data' => "booking_detail_confirm_cancel_{$bookingId}"],
                    ['text' => '❌ Keep Booking', 'callback_data' => "mybooking_view_{$bookingId}"],
                ],
            ];

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting cancellation confirmation', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get modify dates message
     */
    public function getModifyDatesMessage(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getModifyDatesMessage(),
                'action' => 'awaiting_new_checkin_date',
            ];
        } catch (\Exception $e) {
            Log::error('Error getting modify dates message', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get check-in QR
     */
    public function getCheckInQR(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getCheckInQRMessage(),
                'qr_code_url' => $formatter->getQRCodeUrl(),
                'buttons' => [[
                    ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting check-in QR', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get directions
     */
    public function getDirections(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getDirectionsMessage(),
                'buttons' => [[
                    ['text' => '📍 Open in Maps', 'url' => $formatter->getDirectionsUrl()],
                    ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting directions', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get request service message
     */
    public function getRequestServiceMessage(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            $buttons = [
                [
                    ['text' => '🧹 Room Cleaning', 'callback_data' => "booking_service_cleaning_{$bookingId}"],
                    ['text' => '🛁 Extra Towels', 'callback_data' => "booking_service_towels_{$bookingId}"],
                ],
                [
                    ['text' => '🍽️ Room Service', 'callback_data' => "booking_service_food_{$bookingId}"],
                    ['text' => '🔧 Maintenance', 'callback_data' => "booking_service_maintenance_{$bookingId}"],
                ],
                [
                    ['text' => '📝 Other', 'callback_data' => "booking_service_other_{$bookingId}"],
                    ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
                ],
            ];

            return [
                'success' => true,
                'message' => $formatter->getRequestServiceMessage(),
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting request service message', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get extend stay message
     */
    public function getExtendStayMessage(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getExtendStayMessage(),
                'action' => 'awaiting_extend_nights',
            ];
        } catch (\Exception $e) {
            Log::error('Error getting extend stay message', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get review message
     */
    public function getReviewMessage(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getReviewMessage(),
                'action' => 'awaiting_review',
            ];
        } catch (\Exception $e) {
            Log::error('Error getting review message', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Get rebook message
     */
    public function getRebookMessage(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);
            $payment = $booking->payments()->latest()->first();
            $formatter = new \App\Telegram\HotelBookingBot\Formatters\BookingDetailsFormatter($booking, $payment);

            return [
                'success' => true,
                'message' => $formatter->getRebookMessage(),
                'buttons' => [[
                    ['text' => '🔍 Search Rooms', 'callback_data' => 'menu_search'],
                    ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting rebook message', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Booking not found.',
            ];
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            if (!$booking->isCancellable()) {
                return [
                    'success' => false,
                    'message' => '❌ This booking cannot be cancelled.',
                ];
            }

            $booking->update(['status' => 'cancelled']);

            $ref = strtoupper(substr(md5($booking->id), 0, 8));

            return [
                'success' => true,
                'message' => "✅ <b>Booking Cancelled</b>\n\n"
                    . "Booking #{$ref} has been cancelled.\n"
                    . "A cancellation confirmation has been sent to your email.",
                'buttons' => [[
                    ['text' => '📚 My Bookings', 'callback_data' => 'menu_bookings'],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error cancelling booking', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error cancelling booking. Please try again.',
            ];
        }
    }

    /**
     * Check-in booking
     */
    public function checkInBooking(int $bookingId): array
    {
        try {
            $booking = Booking::where('user_id', $this->userId)->findOrFail($bookingId);

            if ($booking->status !== 'confirmed' || !$booking->check_in_date->isToday()) {
                return [
                    'success' => false,
                    'message' => '❌ Check-in is not available for this booking.',
                ];
            }

            $booking->update(['status' => 'checked_in']);

            $ref = strtoupper(substr(md5($booking->id), 0, 8));

            return [
                'success' => true,
                'message' => "🔑 <b>Check-in Successful!</b>\n\n"
                    . "Welcome to SATAAB Hotel!\n"
                    . "Booking #{$ref}\n"
                    . "Room: {$booking->room->name}\n\n"
                    . "Enjoy your stay!",
                'buttons' => [[
                    ['text' => '📚 My Bookings', 'callback_data' => 'menu_bookings'],
                ]],
            ];
        } catch (\Exception $e) {
            Log::error('Error checking in booking', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error checking in. Please try again.',
            ];
        }
    }
}
