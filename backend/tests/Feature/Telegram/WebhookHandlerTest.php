<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    /**
     * Test webhook receives and processes message update
     */
    public function test_webhook_processes_message_update(): void
    {
        $update = [
            'update_id' => 123456789,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'chat' => [
                    'id' => 987654321,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'username' => 'johndoe',
                    'language_code' => 'en',
                ],
                'text' => '/start',
            ],
        ];

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test webhook processes callback query
     */
    public function test_webhook_processes_callback_query(): void
    {
        $update = [
            'update_id' => 123456790,
            'callback_query' => [
                'id' => 'callback_123',
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'John',
                ],
                'chat_instance' => '123456789',
                'data' => 'menu_main',
            ],
        ];

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test webhook handles invalid update gracefully
     */
    public function test_webhook_handles_invalid_update(): void
    {
        $update = [
            'update_id' => 123456791,
        ];

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test webhook returns 200 for all valid updates
     */
    public function test_webhook_returns_200_for_valid_updates(): void
    {
        $updates = [
            ['update_id' => 1, 'message' => ['text' => '/help']],
            ['update_id' => 2, 'callback_query' => ['data' => 'room_1']],
            ['update_id' => 3, 'edited_message' => ['text' => 'edited']],
        ];

        foreach ($updates as $update) {
            $response = $this->postJson('/api/telegram-webhook', $update);
            $response->assertStatus(200);
        }
    }

    /**
     * Test webhook logs update information
     */
    public function test_webhook_logs_update_information(): void
    {
        $update = [
            'update_id' => 123456792,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => 987654321, 'type' => 'private'],
                'from' => ['id' => 987654321, 'is_bot' => false, 'first_name' => 'John'],
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram-webhook', $update);

        $this->assertDatabaseHas('users', [
            'telegram_id' => 987654321,
        ]);
    }
}
