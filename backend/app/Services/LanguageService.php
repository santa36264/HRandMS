<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class LanguageService
{
    const SUPPORTED_LANGUAGES = ['en', 'am'];
    const DEFAULT_LANGUAGE = 'en';

    /**
     * Get user language preference
     */
    public static function getUserLanguage(int $userId): string
    {
        try {
            $user = User::find($userId);

            if ($user && $user->language_preference) {
                return $user->language_preference;
            }

            return self::DEFAULT_LANGUAGE;
        } catch (\Exception $e) {
            Log::error('Error getting user language', ['error' => $e->getMessage()]);
            return self::DEFAULT_LANGUAGE;
        }
    }

    /**
     * Set user language preference
     */
    public static function setUserLanguage(int $userId, string $language): bool
    {
        try {
            if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
                return false;
            }

            $user = User::find($userId);

            if (!$user) {
                return false;
            }

            $user->update(['language_preference' => $language]);

            Log::info('User language preference updated', [
                'user_id' => $userId,
                'language' => $language,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error setting user language', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set application locale
     */
    public static function setLocale(string $language): void
    {
        if (in_array($language, self::SUPPORTED_LANGUAGES)) {
            App::setLocale($language);
        }
    }

    /**
     * Get translation
     */
    public static function trans(string $key, array $replace = [], string $locale = null): string
    {
        if ($locale) {
            return trans($key, $replace, $locale);
        }

        return trans($key, $replace);
    }

    /**
     * Get all supported languages
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'en' => '🇬🇧 English',
            'am' => '🇪🇹 አማርኛ',
        ];
    }

    /**
     * Detect language from Telegram user
     */
    public static function detectLanguageFromTelegram(array $telegramUser): string
    {
        try {
            $languageCode = $telegramUser['language_code'] ?? null;

            if (!$languageCode) {
                return self::DEFAULT_LANGUAGE;
            }

            // Map Telegram language codes to our supported languages
            $languageMap = [
                'en' => 'en',
                'am' => 'am',
                'am-ET' => 'am',
            ];

            return $languageMap[$languageCode] ?? self::DEFAULT_LANGUAGE;
        } catch (\Exception $e) {
            Log::error('Error detecting language', ['error' => $e->getMessage()]);
            return self::DEFAULT_LANGUAGE;
        }
    }

    /**
     * Format message with RTL support for Amharic
     */
    public static function formatMessage(string $message, string $language): string
    {
        if ($language === 'am') {
            // Add RTL marker for Amharic text
            return "\u{202E}" . $message . "\u{202C}";
        }

        return $message;
    }

    /**
     * Get language-specific keyboard buttons
     */
    public static function getLanguageButtons(): array
    {
        return [
            [
                ['text' => '🇬🇧 English', 'callback_data' => 'language_en'],
                ['text' => '🇪🇹 አማርኛ', 'callback_data' => 'language_am'],
            ],
        ];
    }

    /**
     * Check if language is RTL
     */
    public static function isRTL(string $language): bool
    {
        return $language === 'am';
    }
}
