<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Services\RoomService;
use App\Telegram\HotelBookingBot\Formatters\RoomSearchResultFormatter;
use Illuminate\Support\Facades\Log;

class SearchCallbackHandler
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
     * Callback data
     */
    private string $callbackData;

    /**
     * Callback ID
     */
    private string $callbackId;

    /**
     * Room service
     */
    private RoomService $roomService;

    /**
     * Constructor
     */
    public function __construct(int $chatId, int $userId, string $callbackData, string $callbackId, RoomService $roomService)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
        $this->roomService = $roomService;
    }

    /**
     * Handle search callback
     */
    public function handle(): array
    {
        try {
            $stateManager = new SearchStateManager($this->userId);
            $parts = explode('_', $this->callbackData);

            if (count($parts) < 2) {
                return [
                    'success' => false,
                    'callback_id' => $this->callbackId,
                    'message' => '❌ Invalid callback data',
                ];
            }

            $action = $parts[1];

            $response = match ($action) {
                'check_in' => $this->handleCheckInDate($parts, $stateManager),
                'check_out' => $this->handleCheckOutDate($parts, $stateManager),
                'guests' => $this->handleGuestCount($parts, $stateManager),
                'room_type' => $this->handleRoomType($parts, $stateManager),
                'custom_date' => $this->handleCustomDate($stateManager),
                'page' => $this->handlePageChange($parts, $stateManager),
                'sort' => $this->handleSortChange($parts, $stateManager),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling search callback', [
                'error' => $e->getMessage(),
                'callback_data' => $this->callbackData,
            ]);

            return [
                'success' => false,
                'callback_id' => $this->callbackId,
                'message' => '❌ Error processing request',
            ];
        }
    }

    /**
     * Handle check-in date
     */
    private function handleCheckInDate(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid date'];
        }

        $date = $parts[2] . '-' . $parts[3] . '-' . $parts[4];

        $searchCommand = new RoomSearchCommand($this->roomService);
        $result = $searchCommand->handleCheckInDate($date);

        if ($result['success']) {
            $stateManager->setState($result['action']);
            $stateManager->updateSearchCriteria(['check_in_date' => $date]);
        }

        return $result;
    }

    /**
     * Handle check-out date
     */
    private function handleCheckOutDate(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid date'];
        }

        $date = $parts[2] . '-' . $parts[3] . '-' . $parts[4];
        $checkInDate = $stateManager->getCriteria('check_in_date');

        if (!$checkInDate) {
            return ['message' => '❌ Please select check-in date first'];
        }

        $searchCommand = new RoomSearchCommand($this->roomService);
        $result = $searchCommand->handleCheckOutDate($checkInDate, $date);

        if ($result['success']) {
            $stateManager->setState($result['action']);
            $stateManager->updateSearchCriteria([
                'check_out_date' => $date,
            ]);
        }

        return $result;
    }

    /**
     * Handle guest count
     */
    private function handleGuestCount(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid guest count'];
        }

        $guestCount = (int)$parts[2];

        $searchCommand = new RoomSearchCommand($this->roomService);
        $result = $searchCommand->handleGuestCount($guestCount);

        if ($result['success']) {
            $stateManager->setState($result['action']);
            $stateManager->updateSearchCriteria(['guest_count' => $guestCount]);
        }

        return $result;
    }

    /**
     * Handle room type
     */
    private function handleRoomType(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid room type'];
        }

        $roomType = $parts[2];
        $checkInDate = $stateManager->getCriteria('check_in_date');
        $checkOutDate = $stateManager->getCriteria('check_out_date');
        $guestCount = $stateManager->getCriteria('guest_count');

        if (!$checkInDate || !$checkOutDate || !$guestCount) {
            return ['message' => '❌ Missing search criteria'];
        }

        $searchCommand = new RoomSearchCommand($this->roomService);
        $result = $searchCommand->handleRoomTypeAndSearch(
            $checkInDate,
            $checkOutDate,
            $guestCount,
            $roomType
        );

        if ($result['success']) {
            $stateManager->setState($result['action']);
            $stateManager->updateSearchCriteria(['room_type' => $roomType, 'sort_by' => 'price']);
            if (isset($result['results'])) {
                $stateManager->setResults($result['results']);
            }
        }

        return $result;
    }

    /**
     * Handle custom date
     */
    private function handleCustomDate(SearchStateManager $stateManager): array
    {
        return [
            'message' => "📅 <b>Custom Date</b>\n\n"
                . "Please enter the date in format: YYYY-MM-DD\n"
                . "Example: 2024-03-25",
        ];
    }

    /**
     * Handle page change
     */
    private function handlePageChange(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid page number'];
        }

        $page = (int)$parts[2];
        $checkInDate = $stateManager->getCriteria('check_in_date');
        $checkOutDate = $stateManager->getCriteria('check_out_date');
        $guestCount = $stateManager->getCriteria('guest_count');
        $roomType = $stateManager->getCriteria('room_type', 'any');
        $sortBy = $stateManager->getCriteria('sort_by', 'price');
        $results = $stateManager->getCriteria('results', []);

        if (!$checkInDate || !$checkOutDate || !$guestCount || empty($results)) {
            return ['message' => '❌ Search session expired. Please search again.'];
        }

        $checkIn = \Carbon\Carbon::createFromFormat('Y-m-d', $checkInDate);
        $checkOut = \Carbon\Carbon::createFromFormat('Y-m-d', $checkOutDate);

        $formatter = new RoomSearchResultFormatter($results, $checkIn, $checkOut, $guestCount, $page, $sortBy);
        
        $message = $formatter->getFormattedMessage() . "\n" . $formatter->getSummary();

        return [
            'message' => $message,
            'keyboard' => $formatter->getKeyboard(),
        ];
    }

    /**
     * Handle sort change
     */
    private function handleSortChange(array $parts, SearchStateManager $stateManager): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid sort option'];
        }

        $sortBy = $parts[2];
        $checkInDate = $stateManager->getCriteria('check_in_date');
        $checkOutDate = $stateManager->getCriteria('check_out_date');
        $guestCount = $stateManager->getCriteria('guest_count');
        $results = $stateManager->getCriteria('results', []);

        if (!$checkInDate || !$checkOutDate || !$guestCount || empty($results)) {
            return ['message' => '❌ Search session expired. Please search again.'];
        }

        $checkIn = \Carbon\Carbon::createFromFormat('Y-m-d', $checkInDate);
        $checkOut = \Carbon\Carbon::createFromFormat('Y-m-d', $checkOutDate);

        $formatter = new RoomSearchResultFormatter($results, $checkIn, $checkOut, $guestCount, 1, $sortBy);
        
        $stateManager->updateSearchCriteria(['sort_by' => $sortBy]);
        
        $message = $formatter->getFormattedMessage() . "\n" . $formatter->getSummary();

        return [
            'message' => $message,
            'keyboard' => $formatter->getKeyboard(),
        ];
    }
}
