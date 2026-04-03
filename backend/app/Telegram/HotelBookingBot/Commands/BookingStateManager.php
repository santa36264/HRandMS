<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Cache;

class BookingStateManager
{
    /**
     * Telegram user ID
     */
    private int $telegramUserId;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'telegram_booking_';

    /**
     * Cache expiration (30 minutes)
     */
    private const CACHE_EXPIRATION = 1800;

    /**
     * Constructor
     */
    public function __construct(int $telegramUserId)
    {
        $this->telegramUserId = $telegramUserId;
    }

    /**
     * Get current state
     */
    public function getState(): ?string
    {
        return Cache::get($this->getCacheKey('state'));
    }

    /**
     * Set state
     */
    public function setState(string $state): void
    {
        Cache::put($this->getCacheKey('state'), $state, now()->addSeconds(self::CACHE_EXPIRATION));
    }

    /**
     * Get booking data
     */
    public function getBookingData(): array
    {
        return Cache::get($this->getCacheKey('data'), []);
    }

    /**
     * Set booking data
     */
    public function setBookingData(array $data): void
    {
        Cache::put($this->getCacheKey('data'), $data, now()->addSeconds(self::CACHE_EXPIRATION));
    }

    /**
     * Update booking data
     */
    public function updateBookingData(array $data): void
    {
        $current = $this->getBookingData();
        $merged = array_merge($current, $data);
        $this->setBookingData($merged);
    }

    /**
     * Get booking data value
     */
    public function getData(string $key, $default = null)
    {
        $data = $this->getBookingData();
        return $data[$key] ?? $default;
    }

    /**
     * Clear booking state
     */
    public function clearState(): void
    {
        Cache::forget($this->getCacheKey('state'));
        Cache::forget($this->getCacheKey('data'));
    }

    /**
     * Check if booking is in progress
     */
    public function isBookingInProgress(): bool
    {
        $state = $this->getState();
        return in_array($state, [
            'awaiting_guest_count',
            'awaiting_guest_names',
            'awaiting_special_requests',
            'awaiting_confirmation',
        ]);
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $suffix): string
    {
        return self::CACHE_PREFIX . $this->telegramUserId . '_' . $suffix;
    }

    /**
     * Get booking summary
     */
    public function getBookingSummary(): string
    {
        $data = $this->getBookingData();

        $summary = "📋 <b>Booking Summary</b>\n\n";

        if (isset($data['room_name'])) {
            $summary .= "🏨 Room: {$data['room_name']}\n";
        }

        if (isset($data['room_type'])) {
            $summary .= "🏠 Type: {$data['room_type']}\n";
        }

        if (isset($data['check_in_date'])) {
            $summary .= "📅 Check-in: {$data['check_in_date']}\n";
        }

        if (isset($data['check_out_date'])) {
            $summary .= "📅 Check-out: {$data['check_out_date']}\n";
        }

        if (isset($data['nights'])) {
            $summary .= "🌙 Nights: {$data['nights']}\n";
        }

        if (isset($data['guest_count'])) {
            $summary .= "👥 Guests: {$data['guest_count']}\n";
        }

        if (isset($data['total_price'])) {
            $summary .= "💰 Total: {$data['total_price']} ETB\n";
        }

        if (isset($data['guest_names']) && is_array($data['guest_names'])) {
            $summary .= "\n<b>Guest Names:</b>\n";
            foreach ($data['guest_names'] as $index => $name) {
                $summary .= "  " . ($index + 1) . ". {$name}\n";
            }
        }

        if (isset($data['special_requests']) && !empty($data['special_requests'])) {
            $summary .= "\n<b>Special Requests:</b>\n";
            $summary .= "{$data['special_requests']}\n";
        }

        return $summary;
    }

    /**
     * Get time remaining
     */
    public function getTimeRemaining(): int
    {
        $key = $this->getCacheKey('state');
        $ttl = Cache::getStore()->connection()->ttl($key);
        return max(0, $ttl);
    }

    /**
     * Check if session expired
     */
    public function isExpired(): bool
    {
        return $this->getState() === null;
    }
}
