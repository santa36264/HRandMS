<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    /**
     * Statuses that block a room from being booked.
     */
    private const BLOCKING_STATUSES = ['pending', 'confirmed', 'checked_in'];

    /**
     * Return all rooms available for the given date range and filters.
     */
    public function getAvailableRooms(
        string $checkIn,
        string $checkOut,
        ?int   $guests    = null,
        ?string $type     = null,
        ?float $minPrice  = null,
        ?float $maxPrice  = null,
    ): Collection {
        $checkIn  = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();

        $query = Room::query()
            ->where('is_active', true)
            ->where('status', '!=', 'maintenance')
            // ── Core overlap exclusion ──────────────────────────────────────
            // A room is unavailable if ANY blocking booking satisfies:
            //   existing.check_in  < requested.check_out
            //   AND
            //   existing.check_out > requested.check_in
            // This covers all overlap cases:
            //   [--existing--]
            //       [--requested--]   ← partial overlap start
            //   [--existing--]
            //   [--requested--]       ← exact match
            //       [--existing--]
            //   [--requested--]       ← partial overlap end
            //   [----existing----]
            //     [--requested--]     ← existing contains requested
            //   [--requested--]
            //   [----existing----]    ← requested contains existing
            ->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->whereIn('status', self::BLOCKING_STATUSES)
                  ->where('check_in_date', '<', $checkOut)
                  ->where('check_out_date', '>', $checkIn);
            });

        // ── Optional filters ────────────────────────────────────────────────
        if ($guests)   $query->where('capacity', '>=', $guests);
        if ($type)     $query->where('type', $type);
        if ($minPrice) $query->where('price_per_night', '>=', $minPrice);
        if ($maxPrice) $query->where('price_per_night', '<=', $maxPrice);

        $nights = $checkIn->diffInDays($checkOut);

        // Append computed fields to each room
        return $query
            ->orderBy('price_per_night')
            ->get()
            ->each(function (Room $room) use ($nights) {
                $room->nights      = $nights;
                $room->total_price = round($room->price_per_night * $nights, 2);
            });
    }

    /**
     * Check if a specific room is available for the given dates.
     * Optionally exclude a booking ID (for modification flows).
     */
    public function isRoomAvailable(
        int    $roomId,
        string $checkIn,
        string $checkOut,
        ?int   $excludeBookingId = null,
    ): bool {
        $checkIn  = Carbon::parse($checkIn)->startOfDay();
        $checkOut = Carbon::parse($checkOut)->startOfDay();

        $query = Booking::where('room_id', $roomId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->doesntExist();
    }

    /**
     * Assert room is available or throw a validation exception.
     * Use this before creating or modifying a booking.
     */
    public function assertAvailable(
        int    $roomId,
        string $checkIn,
        string $checkOut,
        ?int   $excludeBookingId = null,
    ): void {
        if (! $this->isRoomAvailable($roomId, $checkIn, $checkOut, $excludeBookingId)) {
            throw ValidationException::withMessages([
                'room_id' => [
                    'This room is not available for the selected dates. Please choose different dates or another room.',
                ],
            ]);
        }
    }

    /**
     * Return booked date ranges for a room (for a calendar view).
     */
    public function getBookedDates(int $roomId, ?string $from = null, ?string $until = null): array
    {
        $from  = $from  ? Carbon::parse($from)  : now();
        $until = $until ? Carbon::parse($until) : now()->addMonths(3);

        return Booking::where('room_id', $roomId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->where('check_in_date', '<', $until)
            ->where('check_out_date', '>', $from)
            ->orderBy('check_in_date')
            ->get(['check_in_date', 'check_out_date'])
            ->map(fn($b) => [
                'from'  => $b->check_in_date->toDateString(),
                'until' => $b->check_out_date->toDateString(),
            ])
            ->toArray();
    }
}
