<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RoomService
{
    public function __construct(private AvailabilityService $availability) {}

    /**
     * Filtered, paginated room listing.
     * Used by both Guest (active only) and Admin (all rooms).
     */
    public function filter(array $filters, bool $adminMode = false): LengthAwarePaginator
    {
        $query = Room::query()->withCount('bookings');

        // Admin sees all; guests see active only
        if (! $adminMode) {
            $query->where('is_active', true);
        }

        // ── Text search ────────────────────────────────────────────────────
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('room_number', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        // ── Type & status ──────────────────────────────────────────────────
        if (! empty($filters['type']))   $query->where('type', $filters['type']);
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['floor']))  $query->where('floor', $filters['floor']);

        // ── Capacity ───────────────────────────────────────────────────────
        if (! empty($filters['capacity'])) {
            $query->where('capacity', '>=', (int) $filters['capacity']);
        }

        // ── Price range ────────────────────────────────────────────────────
        if (! empty($filters['min_price'])) {
            $query->where('price_per_night', '>=', (float) $filters['min_price']);
        }
        if (! empty($filters['max_price'])) {
            $query->where('price_per_night', '<=', (float) $filters['max_price']);
        }

        // ── Amenities (must have ALL requested amenities) ──────────────────
        if (! empty($filters['amenities'])) {
            foreach ((array) $filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // ── Date availability (overlap exclusion) ──────────────────────────
        if (! empty($filters['check_in']) && ! empty($filters['check_out'])) {
            $checkIn  = $filters['check_in'];
            $checkOut = $filters['check_out'];

            $query->whereDoesntHave('bookings', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                  ->where('check_in_date', '<', $checkOut)
                  ->where('check_out_date', '>', $checkIn);
            });
        }

        // ── Sorting ────────────────────────────────────────────────────────
        $sortBy  = $filters['sort_by']  ?? 'price_per_night';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        // ── Pagination ─────────────────────────────────────────────────────
        $perPage = (int) ($filters['per_page'] ?? 12);

        return $query->paginate($perPage)->withQueryString();
    }

    public function findForGuest(int $id): Room
    {
        return Room::where('is_active', true)->findOrFail($id);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): Room
    {
        $room->update($data);
        return $room->fresh();
    }

    public function delete(Room $room): void
    {
        // Prevent deletion if room has active bookings
        $hasActive = $room->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->exists();

        if ($hasActive) {
            throw new \RuntimeException(
                'Cannot delete a room with active bookings. Cancel or reassign them first.'
            );
        }

        $room->delete();
    }

    /**
     * Search for available rooms for Telegram bot
     */
    public function searchAvailableRooms($checkInDate, $checkOutDate, int $guestCount, ?string $roomType = null): array
    {
        $query = Room::query()
            ->where('is_active', true)
            ->where('capacity', '>=', $guestCount)
            ->whereDoesntHave('bookings', function (Builder $q) use ($checkInDate, $checkOutDate) {
                $q->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                  ->where('check_in_date', '<', $checkOutDate)
                  ->where('check_out_date', '>', $checkInDate);
            });

        if ($roomType && $roomType !== 'any') {
            $query->where('type', $roomType);
        }

        return $query->get()->map(function (Room $room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'type' => $room->type,
                'capacity' => $room->capacity,
                'price' => $room->price_per_night,
                'rating' => $room->rating ?? 0,
            ];
        })->toArray();
    }
}
