<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit tests for BookingService.
 *
 * Verifies that create() and modify() correctly delegate to
 * AvailabilityService and compute prices accurately.
 */
class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;
    private Room $room;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BookingService(new AvailabilityService());
        $this->room    = Room::factory()->withPrice(1000)->create();
        $this->user    = User::factory()->create();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'room_id'        => $this->room->id,
            'check_in_date'  => '2026-07-01',
            'check_out_date' => '2026-07-05', // 4 nights
            'guests_count'   => 2,
        ], $overrides);
    }

    private function existingBooking(string $checkIn, string $checkOut, string $status = 'confirmed'): Booking
    {
        return Booking::factory()->create([
            'room_id'        => $this->room->id,
            'user_id'        => $this->user->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'status'         => $status,
            'payment_status' => 'paid',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // create()
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function create_stores_booking_with_correct_price_calculation(): void
    {
        // 4 nights × ETB 1000 = ETB 4000
        $booking = $this->service->create($this->user, $this->bookingData());

        $this->assertDatabaseHas('bookings', [
            'id'           => $booking->id,
            'user_id'      => $this->user->id,
            'room_id'      => $this->room->id,
            'total_amount' => 4000.00,
            'final_amount' => 4000.00,
            'status'       => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    /** @test */
    public function create_applies_discount_to_final_amount(): void
    {
        $booking = $this->service->create($this->user, $this->bookingData([
            'discount_amount' => 500,
        ]));

        $this->assertEquals(4000.00, $booking->total_amount);
        $this->assertEquals(500.00,  $booking->discount_amount);
        $this->assertEquals(3500.00, $booking->final_amount);
    }

    /** @test */
    public function create_generates_a_booking_reference(): void
    {
        $booking = $this->service->create($this->user, $this->bookingData());

        $this->assertNotNull($booking->booking_reference);
        $this->assertStringStartsWith('BK-', $booking->booking_reference);
    }

    /** @test */
    public function create_throws_when_room_is_already_booked_for_same_dates(): void
    {
        $this->existingBooking('2026-07-01', '2026-07-05');

        $this->expectException(ValidationException::class);

        $this->service->create($this->user, $this->bookingData());
    }

    /** @test */
    public function create_throws_when_new_booking_partially_overlaps_existing(): void
    {
        $this->existingBooking('2026-07-03', '2026-07-08');

        $this->expectException(ValidationException::class);

        // Requested: Jul 1–5, existing: Jul 3–8 → overlap
        $this->service->create($this->user, $this->bookingData([
            'check_in_date'  => '2026-07-01',
            'check_out_date' => '2026-07-05',
        ]));
    }

    /** @test */
    public function create_succeeds_for_back_to_back_booking_after_existing(): void
    {
        $this->existingBooking('2026-07-01', '2026-07-05');

        // Starts exactly when previous ends — should be allowed
        $booking = $this->service->create($this->user, $this->bookingData([
            'check_in_date'  => '2026-07-05',
            'check_out_date' => '2026-07-09',
        ]));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function create_succeeds_for_back_to_back_booking_before_existing(): void
    {
        $this->existingBooking('2026-07-10', '2026-07-15');

        $booking = $this->service->create($this->user, $this->bookingData([
            'check_in_date'  => '2026-07-05',
            'check_out_date' => '2026-07-10',
        ]));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function create_succeeds_when_conflicting_booking_is_cancelled(): void
    {
        $this->existingBooking('2026-07-01', '2026-07-05', 'cancelled');

        $booking = $this->service->create($this->user, $this->bookingData());

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    /** @test */
    public function create_does_not_persist_booking_when_availability_check_fails(): void
    {
        $this->existingBooking('2026-07-01', '2026-07-05');

        $countBefore = Booking::count();

        try {
            $this->service->create($this->user, $this->bookingData());
        } catch (ValidationException) {
            // expected
        }

        $this->assertEquals($countBefore, Booking::count());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // cancel()
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function cancel_sets_status_to_cancelled(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'confirmed');

        $cancelled = $this->service->cancel($booking, 'Change of plans');

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals('Change of plans', $cancelled->cancellation_reason);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    /** @test */
    public function cancel_throws_when_booking_is_already_checked_in(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'checked_in');

        $this->expectException(ValidationException::class);

        $this->service->cancel($booking);
    }

    /** @test */
    public function cancel_throws_when_booking_is_already_checked_out(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'checked_out');

        $this->expectException(ValidationException::class);

        $this->service->cancel($booking);
    }

    /** @test */
    public function cancelled_booking_frees_room_for_new_booking(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'confirmed');

        $this->service->cancel($booking, 'No longer needed');

        // Same dates should now be bookable
        $newBooking = $this->service->create($this->user, $this->bookingData());

        $this->assertDatabaseHas('bookings', ['id' => $newBooking->id, 'status' => 'pending']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // modify()
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function modify_updates_dates_and_recalculates_price(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'confirmed');
        $booking->load('room');

        // Change to 3 nights → ETB 3000
        $modified = $this->service->modify($booking, [
            'check_in_date'  => '2026-07-01',
            'check_out_date' => '2026-07-04',
            'guests_count'   => 2,
        ]);

        $this->assertEquals('2026-07-04', $modified->check_out_date->toDateString());
        $this->assertEquals(3000.00, $modified->total_amount);
    }

    /** @test */
    public function modify_allows_extending_own_booking_without_conflict(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'confirmed');
        $booking->load('room');

        // Extend by 2 days — overlaps with itself, should be allowed via excludeBookingId
        $modified = $this->service->modify($booking, [
            'check_in_date'  => '2026-07-01',
            'check_out_date' => '2026-07-07',
            'guests_count'   => 2,
        ]);

        $this->assertEquals('2026-07-07', $modified->check_out_date->toDateString());
    }

    /** @test */
    public function modify_throws_when_new_dates_conflict_with_another_booking(): void
    {
        $booking = $this->existingBooking('2026-07-01', '2026-07-05', 'confirmed');
        $booking->load('room');

        // Another booking occupies Jul 8–12
        $this->existingBooking('2026-07-08', '2026-07-12', 'confirmed');

        $this->expectException(ValidationException::class);

        // Try to extend into the other booking's dates
        $this->service->modify($booking, [
            'check_in_date'  => '2026-07-01',
            'check_out_date' => '2026-07-10',
            'guests_count'   => 2,
        ]);
    }
}
