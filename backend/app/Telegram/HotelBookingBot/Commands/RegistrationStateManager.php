<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Models\TelegramVerification;
use Illuminate\Support\Facades\Cache;

class RegistrationStateManager
{
    /**
     * Telegram user ID
     */
    private int $telegramUserId;

    /**
     * Cache key prefix
     */
    private const CACHE_PREFIX = 'telegram_registration_';

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
        Cache::put($this->getCacheKey('state'), $state, now()->addHours(1));
    }

    /**
     * Get state data
     */
    public function getStateData(): array
    {
        return Cache::get($this->getCacheKey('data'), []);
    }

    /**
     * Set state data
     */
    public function setStateData(array $data): void
    {
        Cache::put($this->getCacheKey('data'), $data, now()->addHours(1));
    }

    /**
     * Update state data
     */
    public function updateStateData(array $data): void
    {
        $current = $this->getStateData();
        $merged = array_merge($current, $data);
        $this->setStateData($merged);
    }

    /**
     * Clear state
     */
    public function clearState(): void
    {
        Cache::forget($this->getCacheKey('state'));
        Cache::forget($this->getCacheKey('data'));
    }

    /**
     * Get cache key
     */
    private function getCacheKey(string $suffix): string
    {
        return self::CACHE_PREFIX . $this->telegramUserId . '_' . $suffix;
    }

    /**
     * Check if registration is in progress
     */
    public function isRegistrationInProgress(): bool
    {
        $state = $this->getState();
        return in_array($state, [
            'awaiting_email',
            'awaiting_verification_code',
            'awaiting_name',
            'awaiting_phone',
            'awaiting_password',
        ]);
    }

    /**
     * Get next step message
     */
    public function getNextStepMessage(): ?string
    {
        $state = $this->getState();
        $data = $this->getStateData();

        return match ($state) {
            'awaiting_email' => null,
            'awaiting_verification_code' => "Please enter the 6-digit verification code sent to your email.",
            'awaiting_name' => "Please enter your full name:",
            'awaiting_phone' => "Please enter your phone number (optional, press skip to continue):",
            'awaiting_password' => "Please enter a password for your account:",
            default => null,
        };
    }
}
