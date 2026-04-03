<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\Room;
use App\Services\RoomService;
use App\Telegram\HotelBookingBot\Formatters\RoomSearchResultFormatter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RoomSearchCommand
{
    /**
     * Room service
     */
    private RoomService $roomService;

    /**
     * Constructor
     */
    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Start room search
     */
    public function startSearch(): array
    {
        return [
            'success' => true,
            'message' => "🔍 <b>Room Search</b>\n\n"
                . "Let's find the perfect room for you!\n\n"
                . "First, when would you like to check in?",
            'keyboard' => $this->getDateKeyboard('check_in'),
            'action' => 'awaiting_check_in_date',
        ];
    }

    /**
     * Get date keyboard with date options
     */
    private function getDateKeyboard(string $type): array
    {
        $today = Carbon::today();
        $buttons = [];

        // Add next 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->addDays($i);
            $label = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $date->format('M d'));
            
            $buttons[] = [
                ['text' => $label, 'callback_data' => "search_{$type}_{$date->format('Y-m-d')}"],
            ];
        }

        // Add custom date option
        $buttons[] = [
            ['text' => '📅 Custom Date', 'callback_data' => 'search_custom_date'],
        ];

        return $buttons;
    }

    /**
     * Handle check-in date selection
     */
    public function handleCheckInDate(string $date): array
    {
        try {
            $checkInDate = Carbon::createFromFormat('Y-m-d', $date);

            if ($checkInDate < Carbon::today()) {
                return [
                    'success' => false,
                    'message' => "❌ Check-in date cannot be in the past. Please select a future date.",
                ];
            }

            return [
                'success' => true,
                'message' => "✅ Check-in: <b>{$checkInDate->format('M d, Y')}</b>\n\n"
                    . "Now, when would you like to check out?",
                'keyboard' => $this->getCheckOutDateKeyboard($checkInDate),
                'action' => 'awaiting_check_out_date',
                'check_in_date' => $date,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling check-in date', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "❌ Invalid date format. Please try again.",
            ];
        }
    }

    /**
     * Get check-out date keyboard
     */
    private function getCheckOutDateKeyboard(Carbon $checkInDate): array
    {
        $buttons = [];

        // Add next 7 days starting from check-in + 1
        for ($i = 1; $i <= 7; $i++) {
            $date = $checkInDate->copy()->addDays($i);
            $nights = $i;
            $label = $nights === 1 ? '1 night' : "{$nights} nights";
            
            $buttons[] = [
                ['text' => $date->format('M d') . " ({$label})", 
                 'callback_data' => "search_check_out_{$date->format('Y-m-d')}"],
            ];
        }

        return $buttons;
    }

    /**
     * Handle check-out date selection
     */
    public function handleCheckOutDate(string $checkInDate, string $checkOutDate): array
    {
        try {
            $checkIn = Carbon::createFromFormat('Y-m-d', $checkInDate);
            $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutDate);

            if ($checkOut <= $checkIn) {
                return [
                    'success' => false,
                    'message' => "❌ Check-out date must be after check-in date. Please try again.",
                ];
            }

            $nights = $checkOut->diffInDays($checkIn);

            return [
                'success' => true,
                'message' => "✅ Check-out: <b>{$checkOut->format('M d, Y')}</b>\n"
                    . "Duration: <b>{$nights} night" . ($nights > 1 ? 's' : '') . "</b>\n\n"
                    . "How many guests will be staying?",
                'keyboard' => $this->getGuestCountKeyboard(),
                'action' => 'awaiting_guest_count',
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling check-out date', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "❌ Invalid date format. Please try again.",
            ];
        }
    }

    /**
     * Get guest count keyboard
     */
    private function getGuestCountKeyboard(): array
    {
        $buttons = [];

        for ($i = 1; $i <= 10; $i++) {
            $buttons[] = [
                ['text' => $i . ' ' . ($i === 1 ? 'guest' : 'guests'), 
                 'callback_data' => "search_guests_{$i}"],
            ];
        }

        return $buttons;
    }

    /**
     * Handle guest count selection
     */
    public function handleGuestCount(int $guestCount): array
    {
        return [
            'success' => true,
            'message' => "✅ Guests: <b>{$guestCount}</b>\n\n"
                . "What type of room do you prefer?",
            'keyboard' => $this->getRoomTypeKeyboard(),
            'action' => 'awaiting_room_type',
            'guest_count' => $guestCount,
        ];
    }

    /**
     * Get room type keyboard
     */
    private function getRoomTypeKeyboard(): array
    {
        return [
            [
                ['text' => '🛏️ Single', 'callback_data' => 'search_room_type_single'],
                ['text' => '🛏️🛏️ Double', 'callback_data' => 'search_room_type_double'],
            ],
            [
                ['text' => '👑 Suite', 'callback_data' => 'search_room_type_suite'],
                ['text' => '🔍 Any Type', 'callback_data' => 'search_room_type_any'],
            ],
        ];
    }

    /**
     * Handle room type selection and perform search
     */
    public function handleRoomTypeAndSearch(
        string $checkInDate,
        string $checkOutDate,
        int $guestCount,
        string $roomType
    ): array {
        try {
            $checkIn = Carbon::createFromFormat('Y-m-d', $checkInDate);
            $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutDate);

            // Search for available rooms
            $rooms = $this->roomService->searchAvailableRooms(
                $checkIn,
                $checkOut,
                $guestCount,
                $roomType !== 'any' ? $roomType : null
            );

            if (empty($rooms)) {
                return [
                    'success' => true,
                    'message' => "😔 <b>No Rooms Available</b>\n\n"
                        . "Unfortunately, we don't have any {$roomType} rooms available for:\n"
                        . "📅 {$checkIn->format('M d')} - {$checkOut->format('M d, Y')}\n"
                        . "👥 {$guestCount} guest" . ($guestCount > 1 ? 's' : '') . "\n\n"
                        . "Try different dates or room type.",
                    'keyboard' => [
                        [
                            ['text' => '🔍 New Search', 'callback_data' => 'menu_search_rooms'],
                            ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
                        ]
                    ],
                    'action' => 'search_complete',
                ];
            }

            $formatter = new RoomSearchResultFormatter($rooms, $checkIn, $checkOut, $guestCount);
            
            $message = $formatter->getFormattedMessage() . "\n" . $formatter->getSummary();

            return [
                'success' => true,
                'message' => $message,
                'keyboard' => $formatter->getKeyboard(),
                'action' => 'search_complete',
                'results' => $rooms,
            ];
        } catch (\Exception $e) {
            Log::error('Error performing room search', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "❌ Error searching for rooms. Please try again.",
            ];
        }
    }

    /**
     * Format search results
     */
    private function formatSearchResults($rooms, Carbon $checkIn, Carbon $checkOut, int $guestCount): string
    {
        $nights = $checkOut->diffInDays($checkIn);
        $message = "✅ <b>Available Rooms</b>\n\n"
            . "📅 {$checkIn->format('M d')} - {$checkOut->format('M d, Y')} ({$nights} night" . ($nights > 1 ? 's' : '') . ")\n"
            . "👥 {$guestCount} guest" . ($guestCount > 1 ? 's' : '') . "\n\n";

        foreach ($rooms as $room) {
            $totalPrice = $room['price'] * $nights;
            $message .= "🏠 <b>{$room['name']}</b>\n"
                . "   Type: {$room['type']}\n"
                . "   Capacity: {$room['capacity']} guests\n"
                . "   💰 {$room['price']} ETB/night (Total: {$totalPrice} ETB)\n"
                . "   ⭐ Rating: {$room['rating']}/5\n\n";
        }

        $message .= "Click on a room to see more details and book!";

        return $message;
    }

    /**
     * Get search results keyboard
     */
    private function getSearchResultsKeyboard($rooms): array
    {
        $buttons = [];

        foreach ($rooms as $room) {
            $buttons[] = [
                ['text' => "📋 {$room['name']}", 'callback_data' => "room_details_{$room['id']}"],
            ];
        }

        $buttons[] = [
            ['text' => '🔍 New Search', 'callback_data' => 'menu_search_rooms'],
            ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
        ];

        return $buttons;
    }
}
