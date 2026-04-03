<?php

namespace Tests\Unit\Payments;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PaymentGatewayMockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /**
     * Test mock Chapa payment initiation
     */
    public function test_mock_chapa_payment_initiation(): void
    {
        Http::fake([
            'https://api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'success',
                'data' => [
                    'checkout_url' => 'https://checkout.chapa.co/checkout/payment/test123',
                    'tx_ref' => 'test123',
                ],
            ]),
        ]);

        $response = Http::post('https://api.chapa.co/v1/transaction/initialize', [
            'amount' => 1000,
            'currency' => 'ETB',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'tx_ref' => 'test123',
            'callback_url' => 'https://example.com/payment/callback',
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('data.checkout_url'));
    }

    /**
     * Test mock Chapa payment verification
     */
    public function test_mock_chapa_payment_verification(): void
    {
        Http::fake([
            'https://api.chapa.co/v1/transaction/verify/test123' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'success',
                    'amount' => 1000,
                    'currency' => 'ETB',
                    'reference' => 'test123',
                ],
            ]),
        ]);

        $response = Http::get('https://api.chapa.co/v1/transaction/verify/test123');

        $this->assertEquals('success', $response->json('status'));
        $this->assertEquals('success', $response->json('data.status'));
    }

    /**
     * Test mock Telebirr payment initiation
     */
    public function test_mock_telebirr_payment_initiation(): void
    {
        Http::fake([
            'https://telebirr.com/api/v1/payment/initialize' => Http::response([
                'status' => 'success',
                'data' => [
                    'payment_url' => 'https://telebirr.com/pay/test456',
                    'transaction_id' => 'test456',
                ],
            ]),
        ]);

        $response = Http::post('https://telebirr.com/api/v1/payment/initialize', [
            'amount' => 1000,
            'phone' => '+251911234567',
            'transaction_id' => 'test456',
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('data.payment_url'));
    }

    /**
     * Test mock CBE Birr payment initiation
     */
    public function test_mock_cbe_birr_payment_initiation(): void
    {
        Http::fake([
            'https://cbebirr.et/api/v1/payment/initialize' => Http::response([
                'status' => 'success',
                'data' => [
                    'payment_url' => 'https://cbebirr.et/pay/test789',
                    'reference' => 'test789',
                ],
            ]),
        ]);

        $response = Http::post('https://cbebirr.et/api/v1/payment/initialize', [
            'amount' => 1000,
            'account' => 'test@cbebirr',
            'reference' => 'test789',
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('data.payment_url'));
    }

    /**
     * Test mock payment failure response
     */
    public function test_mock_payment_failure_response(): void
    {
        Http::fake([
            'https://api.chapa.co/v1/transaction/initialize' => Http::response([
                'status' => 'error',
                'message' => 'Invalid amount',
            ], 400),
        ]);

        $response = Http::post('https://api.chapa.co/v1/transaction/initialize', [
            'amount' => -100,
            'currency' => 'ETB',
        ]);

        $this->assertEquals('error', $response->json('status'));
    }

    /**
     * Test mock payment timeout
     */
    public function test_mock_payment_timeout(): void
    {
        Http::fake([
            'https://api.chapa.co/v1/transaction/verify/*' => Http::response([], 504),
        ]);

        $response = Http::get('https://api.chapa.co/v1/transaction/verify/test123');

        $this->assertEquals(504, $response->status());
    }

    /**
     * Test mock payment webhook from Chapa
     */
    public function test_mock_payment_webhook_from_chapa(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'gateway' => 'chapa',
            'status' => 'pending',
        ]);

        $webhookData = [
            'status' => 'success',
            'charge' => [
                'id' => 'charge_' . $payment->id,
                'amount' => $payment->amount,
                'currency' => 'ETB',
                'reference' => 'test123',
            ],
        ];

        $response = $this->postJson('/api/payments/webhook/chapa', $webhookData);

        $response->assertStatus(200);
    }

    /**
     * Test mock refund request
     */
    public function test_mock_refund_request(): void
    {
        Http::fake([
            'https://api.chapa.co/v1/transaction/refund' => Http::response([
                'status' => 'success',
                'data' => [
                    'refund_id' => 'refund_123',
                    'amount' => 1000,
                ],
            ]),
        ]);

        $response = Http::post('https://api.chapa.co/v1/transaction/refund', [
            'transaction_id' => 'test123',
            'amount' => 1000,
        ]);

        $this->assertEquals('success', $response->json('status'));
        $this->assertNotEmpty($response->json('data.refund_id'));
    }
}
