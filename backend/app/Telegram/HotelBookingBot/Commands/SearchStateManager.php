<?php

namespace App\Telegram\HotelBookingBot\Commands;

use Illuminate\Support\Facades\Cache;

class SearchStateManager
{
    /**
     * Telegram user ID
     */
    private int $telegramUserId;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'telegram_search_';

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
        Cache::put($this->getCacheKey('state'), $state, now()->addHours(2));
    }

    /**
     * Get search criteria
     */
    public function getSearchCriteria(): array
    {
        return Cache::get($this->getCacheKey('criteria'), []);
    }

    /**
     * Set search criteria
     */
    public function setSearchCriteria(array $criteria): void
    {
        Cache::put($this->getCacheKey('criteria'), $criteria, now()->addHours(2));
    }

    /**
     * Update search criteria
     */
    public function updateSearchCriteria(array $criteria): void
    {
        $current = $this->getSearchCriteria();
        $merged = array_merge($current, $criteria);
        $this->setSearchCriteria($merged);
    }

    /**
     * Get search criteria value
     */
    public function getCriteria(string $key, $default = null)
    {
        $criteria = $this->getSearchCriteria();
        return $criteria[$key] ?? $default;
    }

    /**
     * Set results
     */
    public function setResults(array $results): void
    {
        Cache::put($this->getCacheKey('results'), $results, now()->addHours(2));
    }

    /**
     * Get results
     */
    public function getResults(): array
    {
        return Cache::get($this->getCacheKey('results'), []);
    }

    /**
     * Clear search state
     */
    public function clearState(): void
    {
        Cache::forget($this->getCacheKey('state'));
        Cache::forget($this->getCacheKey('criteria'));
    }

    /**
     * Check if search is in progress
     */
    public function isSearchInProgress(): bool
    {
        $state = $this->getState();
        return in_array($state, [
            'awaiting_check_in_date',
            'awaiting_check_out_date',
            'awaiting_guest_count',
            'awaiting_room_type',
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
     * Get search summary
     */
    public function getSearchSummary(): string
    {
        $criteria = $this->getSearchCriteria();

        $summary = "📋 <b>Search Summary</b>\n\n";

        if (isset($criteria['check_in_date'])) {
            $summary .= "📅 Check-in: {$criteria['check_in_date']}\n";
        }

        if (isset($criteria['check_out_date'])) {
            $summary .= "📅 Check-out: {$criteria['check_out_date']}\n";
        }

        if (isset($criteria['guest_count'])) {
            $summary .= "👥 Guests: {$criteria['guest_count']}\n";
        }

        if (isset($criteria['room_type'])) {
            $summary .= "🏠 Room Type: {$criteria['room_type']}\n";
        }

        return $summary;
    }
}
