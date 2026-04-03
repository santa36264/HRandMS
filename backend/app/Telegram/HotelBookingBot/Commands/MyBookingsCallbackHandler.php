<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Log;

class MyBookingsCallbackHandler
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
     * Constructor
     */
    public function __construct(
        int $chatId,
        int $userId,
        string $callbackData,
        string $callbackId
    ) {
        $this->chatId = $chatId;
        $this->userId = $userId;
        $this->callbackData = $callbackData;
        $this->callbackId = $callbackId;
    }

    /**
     * Handle my bookings callback
     */
    public function handle(): array
    {
        try {
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
                'view' => $this->handleViewBooking($parts),
                'page' => $this->handlePageChange($parts),
                'filter' => $this->handleFilter(),
                'cancel' => $this->handleCancelBooking($parts),
                'confirm' => $this->handleConfirmCancel($parts),
                'checkin' => $this->handleCheckIn($parts),
                'receipt' => $this->handleViewReceipt($parts),
                'modify' => $this->handleModifyDates($parts),
                'services' => $this->handleAddServices($parts),
                'qr' => $this->handleCheckInQR($parts),
                'directions' => $this->handleDirections($parts),
                'service' => $this->handleRequestService($parts),
                'extend' => $this->handleExtendStay($parts),
                'review' => $this->handleLeaveReview($parts),
                'rebook' => $this->handleRebook($parts),
                default => ['message' => '❌ Unknown action'],
            };

            return [
                'success' => true,
                'callback_id' => $this->callbackId,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error handling my bookings callback', [
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
     * Handle view booking
     */
    private function handleViewBooking(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->showBookingDetails($bookingId);
    }

    /**
     * Handle page change
     */
    private function handlePageChange(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid page number'];
        }

        $page = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->showBookings($page);
    }

    /**
     * Handle filter
     */
    private function handleFilter(): array
    {
        return [
            'message' => "📅 <b>Filter by Date Range</b>\n\n"
                . "Select a date range to filter your bookings:\n\n"
                . "Please enter the start date (format: YYYY-MM-DD):",
            'action' => 'awaiting_filter_start_date',
        ];
    }

    /**
     * Handle cancel booking
     */
    private function handleCancelBooking(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CancellationCommand($this->chatId, $this->userId);

        return $command->getCancellationPolicy($bookingId);
    }

    /**
     * Handle check-in
     */
    private function handleCheckIn(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->checkInBooking($bookingId);
    }

    /**
     * Handle view receipt
     */
    private function handleViewReceipt(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new ReceiptCommand($this->chatId, $this->userId);

        return $command->getBookingDetails($bookingId);
    }

    /**
     * Handle cancel confirmation
     */
    private function handleCancelConfirmation(array $parts): array
    {
        if (count($parts) < 4) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[3];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getCancellationConfirmation($bookingId);
    }

    /**
     * Handle confirm cancel
     */
    private function handleConfirmCancel(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new CancellationCommand($this->chatId, $this->userId);

        return $command->processCancellation($bookingId);
    }

    /**
     * Handle modify dates
     */
    private function handleModifyDates(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getModifyDatesMessage($bookingId);
    }

    /**
     * Handle add services
     */
    private function handleAddServices(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];

        return [
            'message' => "➕ <b>Add Extra Services</b>\n\n"
                . "Coming soon! You'll be able to add services like:\n"
                . "• Airport Transfer\n"
                . "• Breakfast Package\n"
                . "• Spa Services\n"
                . "• Tour Packages\n\n"
                . "Please contact the hotel directly for now.",
            'buttons' => [[
                ['text' => '📞 Contact Hotel', 'callback_data' => 'menu_contact'],
                ['text' => '🔙 Back', 'callback_data' => "mybooking_view_{$bookingId}"],
            ]],
        ];
    }

    /**
     * Handle check-in QR
     */
    private function handleCheckInQR(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getCheckInQR($bookingId);
    }

    /**
     * Handle directions
     */
    private function handleDirections(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getDirections($bookingId);
    }

    /**
     * Handle request service
     */
    private function handleRequestService(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getRequestServiceMessage($bookingId);
    }

    /**
     * Handle extend stay
     */
    private function handleExtendStay(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getExtendStayMessage($bookingId);
    }

    /**
     * Handle leave review
     */
    private function handleLeaveReview(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getReviewMessage($bookingId);
    }

    /**
     * Handle rebook
     */
    private function handleRebook(array $parts): array
    {
        if (count($parts) < 3) {
            return ['message' => '❌ Invalid booking ID'];
        }

        $bookingId = (int)$parts[2];
        $command = new MyBookingsCommand($this->chatId, $this->userId);

        return $command->getRebookMessage($bookingId);
    }
}
