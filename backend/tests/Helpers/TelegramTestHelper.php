<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Room;
use App\Models\Booking;

class TelegramTestHelper
{
    /**
     * Create a test telegram message update
     */
    public static function createMessageUpdate(
        string $text,
        int $telegramUserId = 987654321,
        int $chatId = 987654321,
        string $firstName = 'John',
        string $username = 'johndoe'
    ): array {
        return [
            'update_id' => rand(100000000, 999999999),
            'message' => [
                'message_id' => rand(1, 10000),
                'date' => time(),
                'chat' => [
                    'id' => $chatId,
                    'type' => 'private',
                ],
                'from' => [
                    'id' => $telegramUserId,
                    'is_bot' => false,
                    'first_name' => $firstName,
                    'username' => $username,
                    'language_code' => 'en',
                ],
                'text' => $text,
            ],
        ];
    }

    /**
     * Create a test telegram callback query update
     */
    public static function createCallbackUpdate(
        string $data,
        int $telegramUserId = 987654321,
        string $firstName = 'John',
        string $username = 'johndoe'
    ): array {
        return [
            'update_id' => rand(100000000, 999999999),
            'callback_query' => [
                'id' => 'callback_' . rand(1000, 9999),
                'from' => [
                    'id' => $telegramUserId,
                    'is_bot' => false,
                    'first_name' => $firstName,
                    'username' => $username,
                ],
                'chat_instance' => '123456789',
                'data' => $data,
            ],
        ];
    }

    /**
     * Create a test user with telegram ID
     */
    public static function createTelegramUser(
        int $telegramId = 987654321,
        string $email = 'test@example.com',
        string $name = 'John Doe'
    ): User {
        return User::factory()->create([
            'telegram_id' => $telegramId,
            'email' => $email,
            'name' => $name,
            'language_preference' => 'en',
        ]);
    }

    /**
     * Create a test booking with room
     */
    public static function createTestBooking(
        User $user = null,
        Room $room = null,
        string $status = 'confirmed'
    ): Booking {
        $user = $user ?? User::factory()->create();
        $room = $room ?? Room::factory()->create();

        return Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => $status,
        ]);
    }

    /**
     * Create multiple test rooms
     */
    public static function createTestRooms(int $count = 5): array
    {
        return Room::factory()->count($count)->create()->toArray();
    }

    /**
     * Create a test payment
     */
    public static function createTestPayment(
        Booking $booking = null,
        string $status = 'pending',
        string $gateway = 'chapa'
    ): \App\Models\Payment {
        $booking = $booking ?? self::createTestBooking();

        return \App\Models\Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => $status,
            'gateway' => $gateway,
        ]);
    }

    /**
     * Create a test room service order
     */
    public static function createTestRoomServiceOrder(
        User $user = null,
        string $status = 'pending'
    ): \App\Models\RoomServiceOrder {
        $user = $user ?? User::factory()->create();

        return \App\Models\RoomServiceOrder::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }

    /**
     * Create a test concierge booking
     */
    public static function createTestConciergeBooking(
        User $user = null,
        \App\Models\ConciergeService $service = null,
        string $status = 'confirmed'
    ): \App\Models\ConciergeBooking {
        $user = $user ?? User::factory()->create();
        $service = $service ?? \App\Models\ConciergeService::factory()->create();

        return \App\Models\ConciergeBooking::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => $status,
        ]);
    }

    /**
     * Assert webhook response is successful
     */
    public static function assertWebhookSuccess($response): void
    {
        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Assert webhook response is error
     */
    public static function assertWebhookError($response): void
    {
        $response->assertStatus(200);
        $response->assertJson(['ok' => false]);
    }
}
