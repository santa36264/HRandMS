<?php

namespace Tests\Unit\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Telegram\HotelBookingBot\Commands\LanguageCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LanguageCommandTest extends TestCase
{
    use RefreshDatabase;

    protected int $chatId = 987654321;
    protected int $userId = 987654321;

    /**
     * Test show language menu
     */
    public function test_show_language_menu(): void
    {
        User::factory()->create([
            'id' => $this->userId,
            'telegram_id' => $this->userId,
            'language_preference' => 'en',
        ]);

        $command = new LanguageCommand($this->chatId, $this->userId);
        $result = $command->showLanguageMenu();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('buttons', $result);
        $this->assertStringContainsString('English', $result['message']);
    }

    /**
     * Test change language to amharic
     */
    public function test_change_language_to_amharic(): void
    {
        User::factory()->create([
            'id' => $this->userId,
            'telegram_id' => $this->userId,
            'language_preference' => 'en',
        ]);

        $command = new LanguageCommand($this->chatId, $this->userId);
        $result = $command->changeLanguage('am');

        $this->assertTrue($result['success']);
        $this->assertEquals('am', $result['language']);
        $this->assertDatabaseHas('users', [
            'id' => $this->userId,
            'language_preference' => 'am',
        ]);
    }

    /**
     * Test change language to english
     */
    public function test_change_language_to_english(): void
    {
        User::factory()->create([
            'id' => $this->userId,
            'telegram_id' => $this->userId,
            'language_preference' => 'am',
        ]);

        $command = new LanguageCommand($this->chatId, $this->userId);
        $result = $command->changeLanguage('en');

        $this->assertTrue($result['success']);
        $this->assertEquals('en', $result['language']);
        $this->assertDatabaseHas('users', [
            'id' => $this->userId,
            'language_preference' => 'en',
        ]);
    }

    /**
     * Test change to invalid language
     */
    public function test_change_to_invalid_language(): void
    {
        User::factory()->create([
            'id' => $this->userId,
            'telegram_id' => $this->userId,
            'language_preference' => 'en',
        ]);

        $command = new LanguageCommand($this->chatId, $this->userId);
        $result = $command->changeLanguage('fr');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['message']);
    }

    /**
     * Test show language menu for non-existent user
     */
    public function test_show_language_menu_for_nonexistent_user(): void
    {
        $command = new LanguageCommand($this->chatId, 99999);
        $result = $command->showLanguageMenu();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('buttons', $result);
    }
}
