<?php

namespace App\Telegram\HotelBookingBot\Commands;

use App\Services\LanguageService;
use Illuminate\Support\Facades\Log;

class LanguageCommand
{
    private int $chatId;
    private int $userId;

    public function __construct(int $chatId, int $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;
    }

    /**
     * Show language selection menu
     */
    public function showLanguageMenu(): array
    {
        try {
            $currentLanguage = LanguageService::getUserLanguage($this->userId);

            // Set locale for translation
            LanguageService::setLocale($currentLanguage);

            $message = __('messages.language_select') . "\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "Current language: " . ($currentLanguage === 'en' ? '🇬🇧 English' : '🇪🇹 አማርኛ') . "\n\n";

            $buttons = LanguageService::getLanguageButtons();

            return [
                'success' => true,
                'message' => $message,
                'buttons' => $buttons,
            ];
        } catch (\Exception $e) {
            Log::error('Error showing language menu', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error loading language menu.',
            ];
        }
    }

    /**
     * Change language
     */
    public function changeLanguage(string $language): array
    {
        try {
            $success = LanguageService::setUserLanguage($this->userId, $language);

            if (!$success) {
                return [
                    'success' => false,
                    'message' => '❌ Invalid language selection.',
                ];
            }

            // Set locale for translation
            LanguageService::setLocale($language);

            $languageName = $language === 'en' ? '🇬🇧 English' : '🇪🇹 አማርኛ';
            $message = $language === 'en'
                ? "✅ Language changed to English"
                : "✅ ቋንቋ ወደ አማርኛ ተቀይሯል";

            Log::info('User language changed', [
                'user_id' => $this->userId,
                'language' => $language,
            ]);

            return [
                'success' => true,
                'message' => $message,
                'language' => $language,
            ];
        } catch (\Exception $e) {
            Log::error('Error changing language', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => '❌ Error changing language.',
            ];
        }
    }
}
