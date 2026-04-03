<?php

namespace Tests\Unit\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Services\LanguageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LanguageServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test get user language preference
     */
    public function test_get_user_language_preference(): void
    {
        $user = User::factory()->create(['language_preference' => 'am']);

        $language = LanguageService::getUserLanguage($user->id);

        $this->assertEquals('am', $language);
    }

    /**
     * Test get default language when user has no preference
     */
    public function test_get_default_language_when_no_preference(): void
    {
        $user = User::factory()->create(['language_preference' => null]);

        $language = LanguageService::getUserLanguage($user->id);

        $this->assertEquals('en', $language);
    }

    /**
     * Test set user language preference
     */
    public function test_set_user_language_preference(): void
    {
        $user = User::factory()->create(['language_preference' => 'en']);

        $result = LanguageService::setUserLanguage($user->id, 'am');

        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'language_preference' => 'am',
        ]);
    }

    /**
     * Test set invalid language returns false
     */
    public function test_set_invalid_language_returns_false(): void
    {
        $user = User::factory()->create();

        $result = LanguageService::setUserLanguage($user->id, 'fr');

        $this->assertFalse($result);
    }

    /**
     * Test set language for non-existent user returns false
     */
    public function test_set_language_for_nonexistent_user_returns_false(): void
    {
        $result = LanguageService::setUserLanguage(99999, 'en');

        $this->assertFalse($result);
    }

    /**
     * Test get supported languages
     */
    public function test_get_supported_languages(): void
    {
        $languages = LanguageService::getSupportedLanguages();

        $this->assertArrayHasKey('en', $languages);
        $this->assertArrayHasKey('am', $languages);
        $this->assertEquals('🇬🇧 English', $languages['en']);
        $this->assertEquals('🇪🇹 አማርኛ', $languages['am']);
    }

    /**
     * Test detect language from telegram user
     */
    public function test_detect_language_from_telegram_user(): void
    {
        $telegramUser = [
            'id' => 123456,
            'language_code' => 'am',
        ];

        $language = LanguageService::detectLanguageFromTelegram($telegramUser);

        $this->assertEquals('am', $language);
    }

    /**
     * Test detect language defaults to english
     */
    public function test_detect_language_defaults_to_english(): void
    {
        $telegramUser = [
            'id' => 123456,
            'language_code' => 'fr',
        ];

        $language = LanguageService::detectLanguageFromTelegram($telegramUser);

        $this->assertEquals('en', $language);
    }

    /**
     * Test format message with RTL support
     */
    public function test_format_message_with_rtl_support(): void
    {
        $message = 'Hello World';

        $formatted = LanguageService::formatMessage($message, 'am');

        $this->assertStringContainsString("\u{202E}", $formatted);
        $this->assertStringContainsString("\u{202C}", $formatted);
    }

    /**
     * Test format message without RTL for english
     */
    public function test_format_message_without_rtl_for_english(): void
    {
        $message = 'Hello World';

        $formatted = LanguageService::formatMessage($message, 'en');

        $this->assertEquals($message, $formatted);
    }

    /**
     * Test is RTL for amharic
     */
    public function test_is_rtl_for_amharic(): void
    {
        $this->assertTrue(LanguageService::isRTL('am'));
    }

    /**
     * Test is RTL for english
     */
    public function test_is_rtl_for_english(): void
    {
        $this->assertFalse(LanguageService::isRTL('en'));
    }

    /**
     * Test get language buttons
     */
    public function test_get_language_buttons(): void
    {
        $buttons = LanguageService::getLanguageButtons();

        $this->assertIsArray($buttons);
        $this->assertCount(1, $buttons);
        $this->assertCount(2, $buttons[0]);
        $this->assertEquals('language_en', $buttons[0][0]['callback_data']);
        $this->assertEquals('language_am', $buttons[0][1]['callback_data']);
    }
}
