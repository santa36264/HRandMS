<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommandResponseTest extends TestCase
{
    use RefreshDatabase;

    protected int $telegramUserId = 987654321;
    protected int $chatId = 987654321;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    /**
     * Test /start command response
     */
    public function test_start_command_response(): void
    {
        $update = $this->createMessageUpdate('/start');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('users', [
            'telegram_id' => $this->telegramUserId,
        ]);
    }

    /**
     * Test /help command response
     */
    public function test_help_command_response(): void
    {
        $update = $this->createMessageUpdate('/help');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /rooms command response
     */
    public function test_rooms_command_response(): void
    {
        Room::factory()->count(3)->create();

        $update = $this->createMessageUpdate('/rooms');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /search command response
     */
    public function test_search_command_response(): void
    {
        $update = $this->createMessageUpdate('/search');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /mybookings command response
     */
    public function test_mybookings_command_response(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        Booking::factory()->create(['user_id' => $user->id]);

        $update = $this->createMessageUpdate('/mybookings');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /register command response
     */
    public function test_register_command_response(): void
    {
        $update = $this->createMessageUpdate('/register');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /service command response
     */
    public function test_service_command_response(): void
    {
        $update = $this->createMessageUpdate('/service');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /concierge command response
     */
    public function test_concierge_command_response(): void
    {
        $update = $this->createMessageUpdate('/concierge');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test /language command response
     */
    public function test_language_command_response(): void
    {
        $update = $this->createMessageUpdate('/language');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test unknown command response
     */
    public function test_unknown_command_response(): void
    {
        $update = $this->createMessageUpdate('/unknown');

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Helper: Create message update
     */
    private function createMessageUpdate(string $text): array
    {
        return [
            'update_id' => rand(100000000, 999999999),
            'message' => [
                'message_id' => rand(1, 1000),
                'date' => time(),
                'chat' => [
                    'id' => $this->chatId,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => $this->telegramUserId,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'username' => 'johndoe',
                    'language_code' => 'en',
                ],
                'text' => $text,
            ],
        ];
    }
}
