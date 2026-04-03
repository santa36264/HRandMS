<?php

namespace App\Telegram\HotelBookingBot\Formatters;

use App\Models\Room;

class RoomDetailsFormatter
{
    /**
     * Room data
     */
    private array $room;

    /**
     * Room model instance
     */
    private ?Room $roomModel;

    /**
     * Constructor
     */
    public function __construct(array $room, ?Room $roomModel = null)
    {
        $this->room = $room;
        $this->roomModel = $roomModel;
    }

    /**
     * Get full room description message
     */
    public function getDetailedDescription(): string
    {
        $message = "<b>🏨 {$this->room['name']}</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Room type and capacity
        $message .= "<b>Room Information</b>\n";
        $message .= "🏠 Type: {$this->room['type']}\n";
        $message .= "🪑 Capacity: {$this->room['capacity']} guests\n";
        $message .= "📍 Floor: " . ($this->room['floor'] ?? 'N/A') . "\n";
        $message .= "📐 Size: " . ($this->room['size'] ?? 'N/A') . " m²\n\n";

        // Full description
        if (!empty($this->room['description'])) {
            $message .= "<b>Description</b>\n";
            $message .= "{$this->room['description']}\n\n";
        }

        // Amenities
        $message .= $this->formatAmenities();

        // Pricing
        $message .= $this->formatPricing();

        // Policies
        $message .= $this->formatPolicies();

        // Rating and reviews
        $message .= $this->formatRating();

        return $message;
    }

    /**
     * Format amenities section
     */
    private function formatAmenities(): string
    {
        $amenities = $this->room['amenities'] ?? [];

        if (empty($amenities)) {
            return '';
        }

        $message = "<b>✨ Amenities</b>\n";

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
            'balcony' => '🌅',
            'kitchen' => '🍳',
            'workspace' => '💼',
            'bathtub' => '🛁',
            'shower' => '🚿',
            'hairdryer' => '💇',
            'iron' => '👔',
            'telephone' => '☎️',
        ];

        foreach ($amenities as $amenity) {
            $emoji = $amenityEmojis[strtolower($amenity)] ?? '✓';
            $message .= "{$emoji} {$amenity}\n";
        }

        $message .= "\n";

        return $message;
    }

    /**
     * Format pricing section
     */
    private function formatPricing(): string
    {
        $message = "<b>💰 Pricing</b>\n";
        $message .= "Price per night: <b>{$this->room['price']} ETB</b>\n";

        if (!empty($this->room['discount_percentage'])) {
            $discountedPrice = $this->room['price'] * (1 - $this->room['discount_percentage'] / 100);
            $message .= "Discount: {$this->room['discount_percentage']}% OFF\n";
            $message .= "Discounted price: <b>{$discountedPrice} ETB</b>\n";
        }

        if (!empty($this->room['taxes_percentage'])) {
            $taxAmount = $this->room['price'] * ($this->room['taxes_percentage'] / 100);
            $message .= "Taxes: {$this->room['taxes_percentage']}% ({$taxAmount} ETB)\n";
        }

        $message .= "\n";

        return $message;
    }

    /**
     * Format policies section
     */
    private function formatPolicies(): string
    {
        $message = "<b>📋 Policies</b>\n";

        // Cancellation policy
        $cancellationPolicy = $this->room['cancellation_policy'] ?? 'Free cancellation up to 24 hours before check-in';
        $message .= "🔄 Cancellation: {$cancellationPolicy}\n";

        // Deposit policy
        $depositPolicy = $this->room['deposit_policy'] ?? 'No deposit required';
        $message .= "💳 Deposit: {$depositPolicy}\n";

        // Check-in/Check-out
        $checkInTime = $this->room['check_in_time'] ?? '14:00';
        $checkOutTime = $this->room['check_out_time'] ?? '11:00';
        $message .= "🕐 Check-in: {$checkInTime} | Check-out: {$checkOutTime}\n";

        // Pet policy
        if (!empty($this->room['pet_policy'])) {
            $message .= "🐾 Pets: {$this->room['pet_policy']}\n";
        }

        // Smoking policy
        if (!empty($this->room['smoking_policy'])) {
            $message .= "🚭 Smoking: {$this->room['smoking_policy']}\n";
        }

        $message .= "\n";

        return $message;
    }

    /**
     * Format rating section
     */
    private function formatRating(): string
    {
        $rating = $this->room['rating'] ?? 0;
        $reviewCount = $this->room['review_count'] ?? 0;

        $message = "<b>⭐ Rating</b>\n";
        $message .= "Rating: {$rating}/5 ({$reviewCount} reviews)\n";

        return $message;
    }

    /**
     * Get room photos for media group
     */
    public function getPhotoUrls(): array
    {
        $photos = [];

        if (!empty($this->room['photos']) && is_array($this->room['photos'])) {
            $photos = array_slice($this->room['photos'], 0, 4);
        }

        return $photos;
    }

    /**
     * Get media group for Telegram
     */
    public function getMediaGroup(): array
    {
        $photos = $this->getPhotoUrls();
        $mediaGroup = [];

        foreach ($photos as $index => $photoUrl) {
            $mediaGroup[] = [
                'type' => 'photo',
                'media' => $photoUrl,
                'caption' => $index === 0 ? "<b>{$this->room['name']}</b>" : null,
                'parse_mode' => 'HTML',
            ];
        }

        return $mediaGroup;
    }

    /**
     * Get action buttons
     */
    public function getActionButtons(): array
    {
        return [
            [
                ['text' => '📅 Book This Room', 'callback_data' => "booking_room_{$this->room['id']}"],
                ['text' => '⭐ Add to Favorites', 'callback_data' => "favorite_room_{$this->room['id']}"],
            ],
            [
                ['text' => '🔙 Back to Search', 'callback_data' => 'menu_search_rooms'],
            ],
        ];
    }

    /**
     * Get similar rooms suggestion
     */
    public function getSimilarRoomsSuggestion(): string
    {
        $message = "\n━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💡 <b>Similar Rooms</b>\n";
        $message .= "Interested in other {$this->room['type']} rooms?\n";
        $message .= "Click 'Back to Search' to see more options.\n";

        return $message;
    }

    /**
     * Get complete message with all details
     */
    public function getCompleteMessage(): string
    {
        return $this->getDetailedDescription() . $this->getSimilarRoomsSuggestion();
    }

    /**
     * Check if room has photos
     */
    public function hasPhotos(): bool
    {
        return !empty($this->getPhotoUrls());
    }

    /**
     * Get photo count
     */
    public function getPhotoCount(): int
    {
        return count($this->getPhotoUrls());
    }
}
