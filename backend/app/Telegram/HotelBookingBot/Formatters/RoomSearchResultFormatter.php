<?php

namespace App\Telegram\HotelBookingBot\Formatters;

use Carbon\Carbon;

class RoomSearchResultFormatter
{
    /**
     * Rooms to display
     */
    private array $rooms;

    /**
     * Check-in date
     */
    private Carbon $checkInDate;

    /**
     * Check-out date
     */
    private Carbon $checkOutDate;

    /**
     * Guest count
     */
    private int $guestCount;

    /**
     * Current page
     */
    private int $currentPage;

    /**
     * Rooms per page
     */
    private int $perPage = 5;

    /**
     * Current sort
     */
    private string $sortBy = 'price';

    /**
     * Constructor
     */
    public function __construct(
        array $rooms,
        Carbon $checkInDate,
        Carbon $checkOutDate,
        int $guestCount,
        int $currentPage = 1,
        string $sortBy = 'price'
    ) {
        $this->rooms = $rooms;
        $this->checkInDate = $checkInDate;
        $this->checkOutDate = $checkOutDate;
        $this->guestCount = $guestCount;
        $this->currentPage = $currentPage;
        $this->sortBy = $sortBy;
    }

    /**
     * Get formatted message
     */
    public function getFormattedMessage(): string
    {
        $nights = $this->checkOutDate->diffInDays($this->checkInDate);
        
        $message = "✅ <b>Available Rooms</b>\n\n";
        $message .= "📅 {$this->checkInDate->format('M d')} - {$this->checkOutDate->format('M d, Y')} ({$nights} night" . ($nights > 1 ? 's' : '') . ")\n";
        $message .= "👥 {$this->guestCount} guest" . ($this->guestCount > 1 ? 's' : '') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $sortedRooms = $this->sortRooms($this->rooms);
        $paginatedRooms = $this->paginate($sortedRooms);

        foreach ($paginatedRooms as $index => $room) {
            $message .= $this->formatRoomCard($room, $nights, $index + 1);
        }

        return $message;
    }

    /**
     * Format individual room card
     */
    private function formatRoomCard(array $room, int $nights, int $position): string
    {
        $totalPrice = $room['price'] * $nights;
        $amenities = $this->formatAmenities($room['amenities'] ?? []);
        $description = $this->truncateDescription($room['description'] ?? '', 60);

        $card = "<b>{$position}. 🏨 {$room['type']}</b>\n";
        $card .= "   <b>{$room['name']}</b>\n";
        $card .= "   💰 {$room['price']} ETB/night\n";
        $card .= "   🪑 Max {$room['capacity']} guests\n";
        
        if ($description) {
            $card .= "   📝 {$description}\n";
        }
        
        if ($amenities) {
            $card .= "   ✨ {$amenities}\n";
        }

        $card .= "   💵 Total: <b>{$totalPrice} ETB</b> ({$nights} nights)\n";
        $card .= "   ⭐ Rating: {$room['rating']}/5\n\n";

        return $card;
    }

    /**
     * Format amenities as badges
     */
    private function formatAmenities(array $amenities): string
    {
        if (empty($amenities)) {
            return '';
        }

        $badges = [];
        $amenityEmojis = [
            'wifi' => '📶',
            'ac' => '❄️',
            'tv' => '📺',
            'parking' => '🅿️',
            'pool' => '🏊',
            'gym' => '💪',
            'restaurant' => '🍽️',
            'bar' => '🍷',
            'spa' => '💆',
            'laundry' => '🧺',
            'safe' => '🔒',
            'minibar' => '🧊',
        ];

        foreach (array_slice($amenities, 0, 4) as $amenity) {
            $emoji = $amenityEmojis[strtolower($amenity)] ?? '✓';
            $badges[] = "{$emoji} {$amenity}";
        }

        if (count($amenities) > 4) {
            $badges[] = "+{$count} more";
        }

        return implode(' | ', $badges);
    }

    /**
     * Truncate description
     */
    private function truncateDescription(string $description, int $length): string
    {
        if (strlen($description) <= $length) {
            return $description;
        }

        return substr($description, 0, $length) . '...';
    }

    /**
     * Sort rooms
     */
    private function sortRooms(array $rooms): array
    {
        usort($rooms, function ($a, $b) {
            if ($this->sortBy === 'capacity') {
                return $b['capacity'] <=> $a['capacity'];
            }
            return $a['price'] <=> $b['price'];
        });

        return $rooms;
    }

    /**
     * Paginate rooms
     */
    private function paginate(array $rooms): array
    {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return array_slice($rooms, $offset, $this->perPage);
    }

    /**
     * Get keyboard with room buttons and pagination
     */
    public function getKeyboard(): array
    {
        $sortedRooms = $this->sortRooms($this->rooms);
        $paginatedRooms = $this->paginate($sortedRooms);
        $totalPages = ceil(count($sortedRooms) / $this->perPage);

        $buttons = [];

        // Room action buttons
        foreach ($paginatedRooms as $index => $room) {
            $buttons[] = [
                ['text' => "📋 {$room['name']}", 'callback_data' => "room_details_{$room['id']}"],
                ['text' => "📅 Book", 'callback_data' => "booking_room_{$room['id']}"],
            ];
        }

        // Pagination buttons
        if ($totalPages > 1) {
            $paginationRow = [];
            
            if ($this->currentPage > 1) {
                $paginationRow[] = ['text' => '⬅️ Previous', 'callback_data' => "search_page_" . ($this->currentPage - 1)];
            }

            $paginationRow[] = ['text' => "Page {$this->currentPage}/{$totalPages}", 'callback_data' => 'search_page_info'];

            if ($this->currentPage < $totalPages) {
                $paginationRow[] = ['text' => 'Next ➡️', 'callback_data' => "search_page_" . ($this->currentPage + 1)];
            }

            $buttons[] = $paginationRow;
        }

        // Sort and filter buttons
        $buttons[] = [
            ['text' => $this->sortBy === 'price' ? '💰 Price ✓' : '💰 Price', 'callback_data' => 'search_sort_price'],
            ['text' => $this->sortBy === 'capacity' ? '🪑 Capacity ✓' : '🪑 Capacity', 'callback_data' => 'search_sort_capacity'],
        ];

        // Back button
        $buttons[] = [
            ['text' => '🔍 New Search', 'callback_data' => 'menu_search_rooms'],
            ['text' => '⬅️ Back', 'callback_data' => 'menu_back'],
        ];

        return $buttons;
    }

    /**
     * Get summary with pricing
     */
    public function getSummary(): string
    {
        $nights = $this->checkOutDate->diffInDays($this->checkInDate);
        $sortedRooms = $this->sortRooms($this->rooms);

        if (empty($sortedRooms)) {
            return '';
        }

        $minPrice = min(array_column($sortedRooms, 'price'));
        $maxPrice = max(array_column($sortedRooms, 'price'));
        $minTotal = $minPrice * $nights;
        $maxTotal = $maxPrice * $nights;

        return "💡 <b>Price Range:</b> {$minPrice} - {$maxPrice} ETB/night\n"
            . "💵 <b>Total for {$nights} night" . ($nights > 1 ? 's' : '') . ":</b> {$minTotal} - {$maxTotal} ETB";
    }

    /**
     * Set current page
     */
    public function setCurrentPage(int $page): self
    {
        $this->currentPage = max(1, $page);
        return $this;
    }

    /**
     * Set sort by
     */
    public function setSortBy(string $sortBy): self
    {
        if (in_array($sortBy, ['price', 'capacity'])) {
            $this->sortBy = $sortBy;
        }
        return $this;
    }

    /**
     * Get total rooms count
     */
    public function getTotalRooms(): int
    {
        return count($this->rooms);
    }

    /**
     * Get total pages
     */
    public function getTotalPages(): int
    {
        return ceil(count($this->rooms) / $this->perPage);
    }

    /**
     * Get current page
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get sort by
     */
    public function getSortBy(): string
    {
        return $this->sortBy;
    }
}
