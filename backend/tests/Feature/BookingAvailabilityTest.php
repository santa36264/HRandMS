<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the booking availability API.
 *
 * Tests the full HTTP → Controller → Service → DB stack to ensure
 * the API correctly rejects double bookings and accepts valid ones.
 */
class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $guest;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guest = User::factory()->create(['role' => 'guest']);
        $this->room  = Room::factory()->withPrice(1500)->create([
            'status'    => 'available',
            'is_active' => true,
            'capacity'  => 2,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function actingAsGuest(): static
    {
        return $this->actingAs($this->guest, 'sanctum');
    }

    private function postBooking(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAsGuest()->postJson('/api/guest/bookings', array_merge([
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
            'guests_count'   => 1,
        ], $overrides));
    }

    private function existingBooking(string $checkIn, string $checkOut, string $status = 'confirmed'): Booking
    {
        return Booking::factory()->create([
            'room_id'        => $this->room->id,
            'user_id'        => $this->guest->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'status'         => $status,
            'payment_status' => 'paid',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Successful booking creation
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function guest_can_book_an_available_room(): void
    {
        $response = $this->postBooking();

        $response->assertStatus(201)
                 ->assertJsonPath('data.status', 'pending')
                 ->assertJsonPath('data.payment_status', 'unpaid');

        $this->assertDatabaseHas('bookings', [
            'room_id'        => $this->room->id,
            'user_id'        => $this->guest->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
        ]);
    }

    /** @test */
    public function booking_response_includes_correct_price(): void
    {
        // 4 nights × ETB 1500 = ETB 6000
        $response = $this->postBooking();

        $response->assertStatus(201)
                 ->assertJsonPath('data.total_amount', 6000.0)
                 ->assertJsonPath('data.final_amount', 6000.0);
    }

    /** @test */
    public function booking_response_includes_a_reference_number(): void
    {
        $response = $this->postBooking();

        $response->assertStatus(201);
        $this->assertStringStartsWith('BK-', $response->json('data.booking_reference'));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Double-booking prevention via API
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function api_rejects_booking_for_exact_same_dates_as_existing(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05');

        $response = $this->postBooking();

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('room_id');
    }

    /** @test */
    public function api_rejects_booking_that_starts_inside_existing(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-10');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-05',
            'check_out_date' => '2026-08-12',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('room_id');
    }

    /** @test */
    public function api_rejects_booking_that_ends_inside_existing(): void
    {
        $this->existingBooking('2026-08-05', '2026-08-10');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-07',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('room_id');
    }

    /** @test */
    public function api_rejects_booking_that_contains_existing(): void
    {
        $this->existingBooking('2026-08-03', '2026-08-07');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-10',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('room_id');
    }

    /** @test */
    public function api_rejects_booking_when_existing_contains_new(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-10');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-03',
            'check_out_date' => '2026-08-07',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('room_id');
    }

    /** @test */
    public function double_booking_attempt_does_not_create_a_record(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05');

        $countBefore = Booking::count();

        $this->postBooking();

        $this->assertEquals($countBefore, Booking::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Back-to-back bookings (should succeed)
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function api_allows_booking_starting_on_checkout_day_of_existing(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-05',
            'check_out_date' => '2026-08-09',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function api_allows_booking_ending_on_checkin_day_of_existing(): void
    {
        $this->existingBooking('2026-08-10', '2026-08-15');

        $response = $this->postBooking([
            'check_in_date'  => '2026-08-05',
            'check_out_date' => '2026-08-10',
        ]);

        $response->assertStatus(201);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Non-blocking statuses
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function api_allows_booking_when_only_conflict_is_cancelled(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05', 'cancelled');

        $response = $this->postBooking();

        $response->assertStatus(201);
    }

    /** @test */
    public function api_allows_booking_when_only_conflict_is_checked_out(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05', 'checked_out');

        $response = $this->postBooking();

        $response->assertStatus(201);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Preview endpoint
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function preview_returns_availability_true_when_room_is_free(): void
    {
        $response = $this->actingAsGuest()->getJson('/api/guest/bookings/preview?' . http_build_query([
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
        ]));

        $response->assertOk()
                 ->assertJsonPath('data.is_available', true);
    }

    /** @test */
    public function preview_returns_availability_false_when_room_is_taken(): void
    {
        $this->existingBooking('2026-08-01', '2026-08-05');

        $response = $this->actingAsGuest()->getJson('/api/guest/bookings/preview?' . http_build_query([
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
        ]));

        $response->assertOk()
                 ->assertJsonPath('data.is_available', false);
    }

    /** @test */
    public function preview_calculates_correct_price(): void
    {
        // 4 nights × ETB 1500 = ETB 6000
        $response = $this->actingAsGuest()->getJson('/api/guest/bookings/preview?' . http_build_query([
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
        ]));

        $response->assertOk()
                 ->assertJsonPath('data.nights', 4)
                 ->assertJsonPath('data.total_amount', 6000.0);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Authentication guard
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function unauthenticated_user_cannot_create_booking(): void
    {
        $this->postJson('/api/guest/bookings', [
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
            'guests_count'   => 1,
        ])->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Concurrent booking simulation
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function only_one_booking_succeeds_when_two_users_book_same_room_simultaneously(): void
    {
        $userA = User::factory()->create(['role' => 'guest']);
        $userB = User::factory()->create(['role' => 'guest']);

        $payload = [
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-08-01',
            'check_out_date' => '2026-08-05',
            'guests_count'   => 1,
        ];

        // Simulate sequential requests (true concurrency requires DB-level locking,
        // tested here as sequential to verify the guard logic is correct)
        $responseA = $this->actingAs($userA, 'sanctum')->postJson('/api/guest/bookings', $payload);
        $responseB = $this->actingAs($userB, 'sanctum')->postJson('/api/guest/bookings', $payload);

        $statuses = [$responseA->status(), $responseB->status()];

        $this->assertContains(201, $statuses, 'One booking should succeed.');
        $this->assertContains(422, $statuses, 'One booking should be rejected.');

        // Exactly one booking in DB for these dates
        $this->assertEquals(1, Booking::where('room_id', $this->room->id)
            ->where('check_in_date', '2026-08-01')
            ->where('check_out_date', '2026-08-05')
            ->whereIn('status', ['pending', 'confirmed'])
            ->count()
        );
    }
}
