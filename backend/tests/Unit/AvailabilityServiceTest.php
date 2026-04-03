<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Unit tests for AvailabilityService.
 *
 * Covers every date-overlap scenario, blocking-status logic,
 * the excludeBookingId escape-hatch, and assertAvailable().
 */
class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $service;
    private Room $room;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AvailabilityService();
        $this->room    = Room::factory()->create(['price_per_night' => 1000]);
        $this->user    = User::factory()->create();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Create a booking with the given dates and status.
     */
    private function book(string $checkIn, string $checkOut, string $status = 'confirmed'): Booking
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
    // isRoomAvailable — overlap scenarios
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function room_is_available_when_no_bookings_exist(): void
    {
        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function room_is_unavailable_for_exact_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function room_is_unavailable_when_new_booking_starts_inside_existing(): void
    {
        // Existing: Jun 1–10
        // New:      Jun 5–12  ← starts inside existing
        $this->book('2026-06-01', '2026-06-10');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-05', '2026-06-12')
        );
    }

    /** @test */
    public function room_is_unavailable_when_new_booking_ends_inside_existing(): void
    {
        // Existing: Jun 5–10
        // New:      Jun 1–7   ← ends inside existing
        $this->book('2026-06-05', '2026-06-10');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-07')
        );
    }

    /** @test */
    public function room_is_unavailable_when_new_booking_contains_existing(): void
    {
        // Existing: Jun 3–7
        // New:      Jun 1–10  ← completely wraps existing
        $this->book('2026-06-03', '2026-06-07');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-10')
        );
    }

    /** @test */
    public function room_is_unavailable_when_existing_contains_new_booking(): void
    {
        // Existing: Jun 1–10
        // New:      Jun 3–7   ← completely inside existing
        $this->book('2026-06-01', '2026-06-10');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-03', '2026-06-07')
        );
    }

    /** @test */
    public function room_is_available_when_new_booking_is_entirely_before_existing(): void
    {
        // Existing: Jun 10–15
        // New:      Jun 1–5   ← no overlap
        $this->book('2026-06-10', '2026-06-15');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function room_is_available_when_new_booking_is_entirely_after_existing(): void
    {
        // Existing: Jun 1–5
        // New:      Jun 10–15 ← no overlap
        $this->book('2026-06-01', '2026-06-05');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-10', '2026-06-15')
        );
    }

    /** @test */
    public function room_is_available_when_new_booking_starts_exactly_on_existing_checkout(): void
    {
        // Existing: Jun 1–5
        // New:      Jun 5–10  ← back-to-back, no overlap (checkout day is departure)
        $this->book('2026-06-01', '2026-06-05');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-05', '2026-06-10')
        );
    }

    /** @test */
    public function room_is_available_when_new_booking_ends_exactly_on_existing_checkin(): void
    {
        // Existing: Jun 10–15
        // New:      Jun 5–10  ← back-to-back, no overlap
        $this->book('2026-06-10', '2026-06-15');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-05', '2026-06-10')
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Blocking status logic
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function pending_booking_blocks_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'pending');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function checked_in_booking_blocks_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'checked_in');

        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function cancelled_booking_does_not_block_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'cancelled');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function checked_out_booking_does_not_block_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'checked_out');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    /** @test */
    public function no_show_booking_does_not_block_same_dates(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'no_show');

        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // excludeBookingId — modification flow
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function room_is_available_when_only_conflict_is_the_excluded_booking(): void
    {
        $existing = $this->book('2026-06-01', '2026-06-05');

        // Without exclusion → blocked
        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05')
        );

        // With exclusion → available (modifying own booking)
        $this->assertTrue(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-05', $existing->id)
        );
    }

    /** @test */
    public function exclusion_does_not_ignore_other_conflicting_bookings(): void
    {
        $existing = $this->book('2026-06-01', '2026-06-05');
        $other    = $this->book('2026-06-03', '2026-06-08'); // second user, same room

        // Excluding first booking still blocked by second
        $this->assertFalse(
            $this->service->isRoomAvailable($this->room->id, '2026-06-01', '2026-06-08', $existing->id)
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // assertAvailable
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function assert_available_passes_when_room_is_free(): void
    {
        // Should not throw
        $this->service->assertAvailable($this->room->id, '2026-06-01', '2026-06-05');

        $this->assertTrue(true); // reached here = no exception
    }

    /** @test */
    public function assert_available_throws_validation_exception_when_room_is_taken(): void
    {
        $this->book('2026-06-01', '2026-06-05');

        $this->expectException(ValidationException::class);

        $this->service->assertAvailable($this->room->id, '2026-06-01', '2026-06-05');
    }

    /** @test */
    public function assert_available_exception_targets_room_id_field(): void
    {
        $this->book('2026-06-01', '2026-06-05');

        try {
            $this->service->assertAvailable($this->room->id, '2026-06-01', '2026-06-05');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('room_id', $e->errors());
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // getAvailableRooms
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function get_available_rooms_excludes_rooms_with_overlapping_bookings(): void
    {
        $blockedRoom    = $this->room;
        $availableRoom  = Room::factory()->create(['is_active' => true, 'status' => 'available']);

        $this->book('2026-06-01', '2026-06-10'); // blocks $this->room

        $results = $this->service->getAvailableRooms('2026-06-05', '2026-06-08');

        $ids = $results->pluck('id')->toArray();

        $this->assertNotContains($blockedRoom->id, $ids);
        $this->assertContains($availableRoom->id, $ids);
    }

    /** @test */
    public function get_available_rooms_excludes_inactive_rooms(): void
    {
        $inactive = Room::factory()->inactive()->create();

        $results = $this->service->getAvailableRooms('2026-06-01', '2026-06-05');

        $this->assertNotContains($inactive->id, $results->pluck('id')->toArray());
    }

    /** @test */
    public function get_available_rooms_excludes_maintenance_rooms(): void
    {
        $maintenance = Room::factory()->maintenance()->create(['is_active' => true]);

        $results = $this->service->getAvailableRooms('2026-06-01', '2026-06-05');

        $this->assertNotContains($maintenance->id, $results->pluck('id')->toArray());
    }

    /** @test */
    public function get_available_rooms_appends_nights_and_total_price(): void
    {
        $room = Room::factory()->withPrice(1000)->create(['is_active' => true, 'status' => 'available']);

        $results = $this->service->getAvailableRooms('2026-06-01', '2026-06-04'); // 3 nights

        $found = $results->firstWhere('id', $room->id);

        $this->assertNotNull($found);
        $this->assertEquals(3, $found->nights);
        $this->assertEquals(3000.00, $found->total_price);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // getBookedDates
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function get_booked_dates_returns_blocking_bookings_only(): void
    {
        $this->book('2026-06-01', '2026-06-05', 'confirmed');
        $this->book('2026-06-10', '2026-06-12', 'cancelled'); // should be excluded

        $dates = $this->service->getBookedDates($this->room->id, '2026-05-01', '2026-07-01');

        $this->assertCount(1, $dates);
        $this->assertEquals('2026-06-01', $dates[0]['from']);
        $this->assertEquals('2026-06-05', $dates[0]['until']);
    }

    /** @test */
    public function get_booked_dates_returns_empty_array_when_no_bookings(): void
    {
        $dates = $this->service->getBookedDates($this->room->id);

        $this->assertIsArray($dates);
        $this->assertEmpty($dates);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Multiple rooms — isolation
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function booking_on_one_room_does_not_affect_availability_of_another(): void
    {
        $otherRoom = Room::factory()->create();

        $this->book('2026-06-01', '2026-06-10'); // books $this->room

        $this->assertTrue(
            $this->service->isRoomAvailable($otherRoom->id, '2026-06-01', '2026-06-10')
        );
    }
}
