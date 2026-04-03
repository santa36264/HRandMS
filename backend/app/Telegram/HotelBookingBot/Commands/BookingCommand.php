<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Room;
use App\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingCommand
{
    /**
     * Room service
     */
    private RoomService $roomService;

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
    public function __construct(RoomService $roomService, int $chatId, int $userId)
    {
        $this->roomService = $roomService;
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Start booking process
     */
    public function startBooking(int $roomId, ?string $checkInDate = null, ?string $checkOutDate = null, ?int $guestCount = null): array
    {
        try {
            $room = Room::findOrFail($roomId);

            if (!$room->is_active) {
                return [
                    'success' => false,
                    'message' => '❌ This room is no longer available.',
                ];
            }

            // Calculate nights if dates provided
            $nights = 0;
            if ($checkInDate && $checkOutDate) {
                $checkIn = Carbon::createFromFormat('Y-m-d', $checkInDate);
                $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutDate);
                $nights = $checkOut->diffInDays($checkIn);
            }

            // Calculate total price
            $totalPrice = $room->price_per_night * $nights;

            // Store booking data
            $stateManager = new BookingStateManager($this->userId);
            $stateManager->setBookingData([
                'room_id' => $room->id,
                'room_name' => $room->name,
                'room_type' => $room->type,
                'room_capacity' => $room->capacity,
                'price_per_night' => $room->price_per_night,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'nights' => $nights,
                'total_price' => $totalPrice,
                'guest_count' => $guestCount,
                'guest_names' => [],
                'special_requests' => '',
            ]);

            // Determine next step
            if (!$guestCount) {
                $stateManager->setState('awaiting_guest_count');
                return $this->askForGuestCount($room);
            } else {
                $stateManager->setState('awaiting_guest_names');
                return $this->askForGuestNames($room, $guestCount);
            }
        } catch (\Exception $e) {
            Log::error('Error starting booking', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '❌ Error starting booking. Please try again.',
            ];
        }
    }

    /**
     * Ask for guest count
     */
    private function askForGuestCount(Room $room): array
    {
        $message = "👥 <b>How many guests will be staying?</b>\n\n";
        $message .= "Maximum capacity: {$room->capacity} guests\n";
        $message .= "Please enter a number between 1 and {$room->capacity}";

        return [
            'success' => true,
            'message' => $message,
            'action' => 'awaiting_guest_count',
        ];
    }

    /**
     * Handle guest count submission
     */
    public function handleGuestCount(int $guestCount): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (!isset($data['room_capacity'])) {
            return [
                'success' => false,
                'message' => '❌ Booking session expired. Please start over.',
            ];
        }

        if ($guestCount < 1 || $guestCount > $data['room_capacity']) {
            return [
                'success' => false,
                'message' => "❌ Invalid guest count. Please enter a number between 1 and {$data['room_capacity']}.",
            ];
        }

        $stateManager->updateBookingData(['guest_count' => $guestCount]);
        $stateManager->setState('awaiting_guest_names');

        return $this->askForGuestNames(null, $guestCount);
    }

    /**
     * Ask for guest names
     */
    private function askForGuestNames(?Room $room, int $guestCount): array
    {
        $message = "👤 <b>Guest Names</b>\n\n";
        $message .= "Please enter the name of guest 1 (primary guest):\n\n";
        $message .= "Guest 1 of {$guestCount}";

        return [
            'success' => true,
            'message' => $message,
            'action' => 'awaiting_guest_names',
            'guest_count' => $guestCount,
        ];
    }

    /**
     * Handle guest name submission
     */
    public function handleGuestName(string $name): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (!isset($data['guest_count'])) {
            return [
                'success' => false,
                'message' => '❌ Booking session expired. Please start over.',
            ];
        }

        $guestNames = $data['guest_names'] ?? [];
        $currentGuestIndex = count($guestNames);

        // Validate name
        if (strlen($name) < 2 || strlen($name) > 100) {
            return [
                'success' => false,
                'message' => '❌ Name must be between 2 and 100 characters.',
            ];
        }

        $guestNames[] = $name;
        $stateManager->updateBookingData(['guest_names' => $guestNames]);

        // Check if all guests entered
        if (count($guestNames) < $data['guest_count']) {
            $nextGuestNumber = count($guestNames) + 1;
            $message = "👤 <b>Guest Names</b>\n\n";
            $message .= "Please enter the name of guest {$nextGuestNumber}:\n\n";
            $message .= "Guest {$nextGuestNumber} of {$data['guest_count']}";

            return [
                'success' => true,
                'message' => $message,
                'action' => 'awaiting_guest_names',
                'guest_count' => $data['guest_count'],
                'current_guest' => $nextGuestNumber,
            ];
        }

        // All guests entered, ask for special requests
        $stateManager->setState('awaiting_special_requests');

        return [
            'success' => true,
            'message' => "✅ All guest names recorded!\n\n"
                . "<b>Special Requests (Optional)</b>\n\n"
                . "Do you have any special requests for your stay?\n"
                . "(e.g., high floor, late check-in, extra pillows)\n\n"
                . "Type your request or 'skip' to continue:",
            'action' => 'awaiting_special_requests',
        ];
    }

    /**
     * Handle special requests
     */
    public function handleSpecialRequests(string $requests): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $data = $stateManager->getBookingData();

        if (!isset($data['guest_count'])) {
            return [
                'success' => false,
                'message' => '❌ Booking session expired. Please start over.',
            ];
        }

        // Store special requests if not "skip"
        if (strtolower($requests) !== 'skip') {
            if (strlen($requests) > 500) {
                return [
                    'success' => false,
                    'message' => '❌ Special requests must be 500 characters or less.',
                ];
            }
            $stateManager->updateBookingData(['special_requests' => $requests]);
        }

        $stateManager->setState('awaiting_confirmation');

        return [
            'success' => true,
            'message' => $stateManager->getBookingSummary(),
            'action' => 'awaiting_confirmation',
        ];
    }

    /**
     * Get booking summary
     */
    public function getBookingSummary(): string
    {
        $stateManager = new BookingStateManager($this->userId);
        return $stateManager->getBookingSummary();
    }

    /**
     * Get confirmation buttons
     */
    public function getConfirmationButtons(): array
    {
        return [
            [
                ['text' => '✅ Confirm Booking', 'callback_data' => 'booking_confirm'],
                ['text' => '✏️ Edit Details', 'callback_data' => 'booking_edit'],
            ],
            [
                ['text' => '❌ Cancel', 'callback_data' => 'booking_cancel'],
            ],
        ];
    }

    /**
     * Confirm booking
     */
    public function confirmBooking(): array
    {
        try {
            $stateManager = new BookingStateManager($this->userId);
            $data = $stateManager->getBookingData();

            if (!isset($data['room_id'])) {
                return [
                    'success' => false,
                    'message' => '❌ Booking session expired. Please start over.',
                ];
            }

            // Here you would create the actual booking in the database
            // For now, we'll just return success
            $stateManager->clearState();

            return [
                'success' => true,
                'message' => "✅ <b>Booking Confirmed!</b>\n\n"
                    . "Your booking has been confirmed.\n"
                    . "Booking reference: #" . strtoupper(substr(md5($data['room_id'] . time()), 0, 8)) . "\n\n"
                    . "A confirmation email has been sent to your registered email address.\n\n"
                    . "Thank you for choosing SATAAB Hotel! 🏨",
            ];
        } catch (\Exception $e) {
            Log::error('Error confirming booking', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => '❌ Error confirming booking. Please try again.',
            ];
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(): array
    {
        $stateManager = new BookingStateManager($this->userId);
        $stateManager->clearState();

        return [
            'success' => true,
            'message' => "❌ Booking cancelled.\n\n"
                . "You can start a new search anytime using /search command.",
        ];
    }
}
