<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use Tests\Helpers\TelegramTestHelper;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete user journey: registration -> search -> booking -> payment
     */
    public function test_complete_user_journey(): void
    {
        // Step 1: User starts bot
        $startUpdate = TelegramTestHelper::createMessageUpdate('/start');
        $response = $this->postJson('/api/telegram-webhook', $startUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 2: User registers
        $registerUpdate = TelegramTestHelper::createMessageUpdate('/register');
        $response = $this->postJson('/api/telegram-webhook', $registerUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 3: User searches for rooms
        $searchUpdate = TelegramTestHelper::createMessageUpdate('/search');
        $response = $this->postJson('/api/telegram-webhook', $searchUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 4: User views available rooms
        $roomsUpdate = TelegramTestHelper::createMessageUpdate('/rooms');
        $response = $this->postJson('/api/telegram-webhook', $roomsUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 5: User selects a room for booking
        $room = Room::factory()->create();
        $bookingUpdate = TelegramTestHelper::createCallbackUpdate('booking_room_' . $room->id);
        $response = $this->postJson('/api/telegram-webhook', $bookingUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 6: User provides guest count
        $guestCountUpdate = TelegramTestHelper::createMessageUpdate('2');
        $response = $this->postJson('/api/telegram-webhook', $guestCountUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 7: User provides guest name
        $guestNameUpdate = TelegramTestHelper::createMessageUpdate('John Doe');
        $response = $this->postJson('/api/telegram-webhook', $guestNameUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 8: User provides special requests
        $requestsUpdate = TelegramTestHelper::createMessageUpdate('High floor');
        $response = $this->postJson('/api/telegram-webhook', $requestsUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 9: User confirms booking
        $confirmUpdate = TelegramTestHelper::createCallbackUpdate('booking_confirm');
        $response = $this->postJson('/api/telegram-webhook', $confirmUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Verify booking was created
        $this->assertDatabaseHas('bookings', [
            'room_id' => $room->id,
        ]);
    }

    /**
     * Test room service ordering flow
     */
    public function test_room_service_ordering_flow(): void
    {
        $user = TelegramTestHelper::createTelegramUser();

        // Step 1: Open room service
        $serviceUpdate = TelegramTestHelper::createMessageUpdate('/service', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $serviceUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 2: Select breakfast category
        $categoryUpdate = TelegramTestHelper::createCallbackUpdate('service_breakfast', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $categoryUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 3: Select item
        $itemUpdate = TelegramTestHelper::createCallbackUpdate('service_item_1', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $itemUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 4: Add to cart
        $cartUpdate = TelegramTestHelper::createCallbackUpdate('service_add_1', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $cartUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 5: Checkout
        $checkoutUpdate = TelegramTestHelper::createCallbackUpdate('service_checkout', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $checkoutUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);
    }

    /**
     * Test concierge service booking flow
     */
    public function test_concierge_service_booking_flow(): void
    {
        $user = TelegramTestHelper::createTelegramUser();

        // Step 1: Open concierge
        $conciergeUpdate = TelegramTestHelper::createMessageUpdate('/concierge', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $conciergeUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 2: Select airport service
        $serviceUpdate = TelegramTestHelper::createCallbackUpdate('concierge_airport', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $serviceUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 3: Select time slot
        $timeUpdate = TelegramTestHelper::createCallbackUpdate('concierge_time_1', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $timeUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 4: Confirm booking
        $confirmUpdate = TelegramTestHelper::createCallbackUpdate('concierge_confirm', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $confirmUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Verify concierge booking was created
        $this->assertDatabaseHas('concierge_bookings', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test language switching flow
     */
    public function test_language_switching_flow(): void
    {
        $user = TelegramTestHelper::createTelegramUser();

        // Step 1: Open language menu
        $menuUpdate = TelegramTestHelper::createMessageUpdate('/language', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $menuUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Step 2: Switch to Amharic
        $amharicUpdate = TelegramTestHelper::createCallbackUpdate('language_am', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $amharicUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Verify language was updated
        $user->refresh();
        $this->assertEquals('am', $user->language_preference);

        // Step 3: Switch back to English
        $englishUpdate = TelegramTestHelper::createCallbackUpdate('language_en', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $englishUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Verify language was updated
        $user->refresh();
        $this->assertEquals('en', $user->language_preference);
    }

    /**
     * Test multiple users interacting simultaneously
     */
    public function test_multiple_users_interacting_simultaneously(): void
    {
        $user1 = TelegramTestHelper::createTelegramUser(111111111, 'user1@example.com');
        $user2 = TelegramTestHelper::createTelegramUser(222222222, 'user2@example.com');

        // User 1 searches for rooms
        $user1SearchUpdate = TelegramTestHelper::createMessageUpdate('/search', $user1->telegram_id);
        $response1 = $this->postJson('/api/telegram-webhook', $user1SearchUpdate);
        TelegramTestHelper::assertWebhookSuccess($response1);

        // User 2 opens room service
        $user2ServiceUpdate = TelegramTestHelper::createMessageUpdate('/service', $user2->telegram_id);
        $response2 = $this->postJson('/api/telegram-webhook', $user2ServiceUpdate);
        TelegramTestHelper::assertWebhookSuccess($response2);

        // User 1 views bookings
        $user1BookingsUpdate = TelegramTestHelper::createMessageUpdate('/mybookings', $user1->telegram_id);
        $response1 = $this->postJson('/api/telegram-webhook', $user1BookingsUpdate);
        TelegramTestHelper::assertWebhookSuccess($response1);

        // User 2 switches language
        $user2LanguageUpdate = TelegramTestHelper::createCallbackUpdate('language_am', $user2->telegram_id);
        $response2 = $this->postJson('/api/telegram-webhook', $user2LanguageUpdate);
        TelegramTestHelper::assertWebhookSuccess($response2);

        // Verify both users' data is isolated
        $user1->refresh();
        $user2->refresh();
        $this->assertEquals('en', $user1->language_preference);
        $this->assertEquals('am', $user2->language_preference);
    }

    /**
     * Test error handling in conversation flow
     */
    public function test_error_handling_in_conversation_flow(): void
    {
        $user = TelegramTestHelper::createTelegramUser();

        // Send invalid guest count
        $invalidCountUpdate = TelegramTestHelper::createMessageUpdate('invalid', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $invalidCountUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Send unknown command
        $unknownUpdate = TelegramTestHelper::createMessageUpdate('/unknown', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $unknownUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);

        // Send empty message
        $emptyUpdate = TelegramTestHelper::createMessageUpdate('', $user->telegram_id);
        $response = $this->postJson('/api/telegram-webhook', $emptyUpdate);
        TelegramTestHelper::assertWebhookSuccess($response);
    }
}
