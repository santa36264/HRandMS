<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversationFlowTest extends TestCase
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
     * Test registration flow: email -> verification -> name -> phone
     */
    public function test_registration_conversation_flow(): void
    {
        // Step 1: Start registration
        $startUpdate = $this->createMessageUpdate('/register');
        $response = $this->postJson('/api/telegram-webhook', $startUpdate);
        $response->assertStatus(200);

        // Step 2: Submit email
        $emailUpdate = $this->createMessageUpdate('test@example.com');
        $response = $this->postJson('/api/telegram-webhook', $emailUpdate);
        $response->assertStatus(200);

        // Step 3: Submit verification code (mock)
        $codeUpdate = $this->createMessageUpdate('123456');
        $response = $this->postJson('/api/telegram-webhook', $codeUpdate);
        $response->assertStatus(200);

        // Step 4: Submit name
        $nameUpdate = $this->createMessageUpdate('John Doe');
        $response = $this->postJson('/api/telegram-webhook', $nameUpdate);
        $response->assertStatus(200);

        // Step 5: Submit phone
        $phoneUpdate = $this->createMessageUpdate('+251911234567');
        $response = $this->postJson('/api/telegram-webhook', $phoneUpdate);
        $response->assertStatus(200);
    }

    /**
     * Test booking conversation flow: room selection -> guest count -> guest names -> special requests
     */
    public function test_booking_conversation_flow(): void
    {
        $room = Room::factory()->create();

        // Step 1: Start booking
        $startUpdate = $this->createCallbackUpdate('booking_room_' . $room->id);
        $response = $this->postJson('/api/telegram-webhook', $startUpdate);
        $response->assertStatus(200);

        // Step 2: Submit guest count
        $guestCountUpdate = $this->createMessageUpdate('2');
        $response = $this->postJson('/api/telegram-webhook', $guestCountUpdate);
        $response->assertStatus(200);

        // Step 3: Submit guest names
        $guestNameUpdate = $this->createMessageUpdate('John Doe');
        $response = $this->postJson('/api/telegram-webhook', $guestNameUpdate);
        $response->assertStatus(200);

        // Step 4: Submit special requests
        $requestsUpdate = $this->createMessageUpdate('High floor, quiet room');
        $response = $this->postJson('/api/telegram-webhook', $requestsUpdate);
        $response->assertStatus(200);
    }

    /**
     * Test room search conversation flow
     */
    public function test_room_search_conversation_flow(): void
    {
        Room::factory()->count(5)->create();

        // Step 1: Start search
        $startUpdate = $this->createMessageUpdate('/search');
        $response = $this->postJson('/api/telegram-webhook', $startUpdate);
        $response->assertStatus(200);

        // Step 2: Select check-in date
        $checkInUpdate = $this->createCallbackUpdate('search_checkin');
        $response = $this->postJson('/api/telegram-webhook', $checkInUpdate);
        $response->assertStatus(200);

        // Step 3: Select check-out date
        $checkOutUpdate = $this->createCallbackUpdate('search_checkout');
        $response = $this->postJson('/api/telegram-webhook', $checkOutUpdate);
        $response->assertStatus(200);

        // Step 4: View results
        $resultsUpdate = $this->createCallbackUpdate('search_results');
        $response = $this->postJson('/api/telegram-webhook', $resultsUpdate);
        $response->assertStatus(200);
    }

    /**
     * Test language selection flow
     */
    public function test_language_selection_flow(): void
    {
        // Step 1: Open language menu
        $menuUpdate = $this->createMessageUpdate('/language');
        $response = $this->postJson('/api/telegram-webhook', $menuUpdate);
        $response->assertStatus(200);

        // Step 2: Select English
        $englishUpdate = $this->createCallbackUpdate('language_en');
        $response = $this->postJson('/api/telegram-webhook', $englishUpdate);
        $response->assertStatus(200);

        // Verify language preference saved
        $user = User::where('telegram_id', $this->telegramUserId)->first();
        $this->assertEquals('en', $user->language_preference ?? 'en');

        // Step 3: Switch to Amharic
        $amharicUpdate = $this->createCallbackUpdate('language_am');
        $response = $this->postJson('/api/telegram-webhook', $amharicUpdate);
        $response->assertStatus(200);

        // Verify language preference updated
        $user->refresh();
        $this->assertEquals('am', $user->language_preference ?? 'am');
    }

    /**
     * Test room service ordering flow
     */
    public function test_room_service_ordering_flow(): void
    {
        // Step 1: Open room service menu
        $menuUpdate = $this->createMessageUpdate('/service');
        $response = $this->postJson('/api/telegram-webhook', $menuUpdate);
        $response->assertStatus(200);

        // Step 2: Select category
        $categoryUpdate = $this->createCallbackUpdate('service_breakfast');
        $response = $this->postJson('/api/telegram-webhook', $categoryUpdate);
        $response->assertStatus(200);

        // Step 3: Select item
        $itemUpdate = $this->createCallbackUpdate('service_item_1');
        $response = $this->postJson('/api/telegram-webhook', $itemUpdate);
        $response->assertStatus(200);

        // Step 4: Add to cart
        $cartUpdate = $this->createCallbackUpdate('service_add_1');
        $response = $this->postJson('/api/telegram-webhook', $cartUpdate);
        $response->assertStatus(200);

        // Step 5: Checkout
        $checkoutUpdate = $this->createCallbackUpdate('service_checkout');
        $response = $this->postJson('/api/telegram-webhook', $checkoutUpdate);
        $response->assertStatus(200);
    }

    /**
     * Test concierge booking flow
     */
    public function test_concierge_booking_flow(): void
    {
        // Step 1: Open concierge menu
        $menuUpdate = $this->createMessageUpdate('/concierge');
        $response = $this->postJson('/api/telegram-webhook', $menuUpdate);
        $response->assertStatus(200);

        // Step 2: Select service type
        $serviceUpdate = $this->createCallbackUpdate('concierge_airport');
        $response = $this->postJson('/api/telegram-webhook', $serviceUpdate);
        $response->assertStatus(200);

        // Step 3: Select time slot
        $timeUpdate = $this->createCallbackUpdate('concierge_time_1');
        $response = $this->postJson('/api/telegram-webhook', $timeUpdate);
        $response->assertStatus(200);

        // Step 4: Confirm booking
        $confirmUpdate = $this->createCallbackUpdate('concierge_confirm');
        $response = $this->postJson('/api/telegram-webhook', $confirmUpdate);
        $response->assertStatus(200);
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

    /**
     * Helper: Create callback update
     */
    private function createCallbackUpdate(string $data): array
    {
        return [
            'update_id' => rand(100000000, 999999999),
            'callback_query' => [
                'id' => 'callback_' . rand(1000, 9999),
                'from' => [
                    'id' => $this->telegramUserId,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'username' => 'johndoe',
                ],
                'chat_instance' => '123456789',
                'data' => $data,
            ],
        ];
    }
}
