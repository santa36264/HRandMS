<?php

namespace Tests\Feature\Telegram;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentIntegrationTest extends TestCase
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
     * Test payment callback handling
     */
    public function test_payment_callback_handling(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        $update = $this->createCallbackUpdate('payment_initiate_' . $booking->id);

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test payment confirmation callback
     */
    public function test_payment_confirmation_callback(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
        ]);

        $update = $this->createCallbackUpdate('payment_confirm_' . $payment->id);

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test payment cancellation callback
     */
    public function test_payment_cancellation_callback(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
        ]);

        $update = $this->createCallbackUpdate('payment_cancel_' . $payment->id);

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test payment webhook from gateway
     */
    public function test_payment_gateway_webhook(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'pending',
            'gateway' => 'chapa',
        ]);

        $webhookData = [
            'status' => 'success',
            'charge' => [
                'id' => 'charge_' . $payment->id,
                'amount' => $payment->amount,
                'currency' => 'ETB',
            ],
        ];

        $response = $this->postJson('/api/payments/webhook/chapa', $webhookData);

        $response->assertStatus(200);
    }

    /**
     * Test payment retry callback
     */
    public function test_payment_retry_callback(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'pending',
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'failed',
        ]);

        $update = $this->createCallbackUpdate('payment_retry_' . $payment->id);

        $response = $this->postJson('/api/telegram-webhook', $update);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    /**
     * Test checkout flow with payment
     */
    public function test_checkout_flow_with_payment(): void
    {
        $user = User::factory()->create(['telegram_id' => $this->telegramUserId]);
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);

        // Step 1: Initiate checkout
        $checkoutUpdate = $this->createCallbackUpdate('checkout_' . $booking->id);
        $response = $this->postJson('/api/telegram-webhook', $checkoutUpdate);
        $response->assertStatus(200);

        // Step 2: Select payment method
        $methodUpdate = $this->createCallbackUpdate('payment_method_chapa');
        $response = $this->postJson('/api/telegram-webhook', $methodUpdate);
        $response->assertStatus(200);
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
                ],
                'chat_instance' => '123456789',
                'data' => $data,
            ],
        ];
    }
}
