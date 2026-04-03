<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private AvailabilityService $availability) {}

    public function create(User $user, array $data): Booking
    {
        // Guard against double booking before insert
        $this->availability->assertAvailable(
            $data['room_id'],
            $data['check_in_date'],
            $data['check_out_date'],
        );

        $room    = Room::findOrFail($data['room_id']);
        $nights  = \Carbon\Carbon::parse($data['check_in_date'])
                        ->diffInDays($data['check_out_date']);

        $total    = round($room->price_per_night * $nights, 2);
        $discount = $data['discount_amount'] ?? 0;

        return Booking::create([
            'user_id'          => $user->id,
            'room_id'          => $room->id,
            'check_in_date'    => $data['check_in_date'],
            'check_out_date'   => $data['check_out_date'],
            'guests_count'     => $data['guests_count'] ?? 1,
            'total_amount'     => $total,
            'discount_amount'  => $discount,
            'final_amount'     => $total - $discount,
            'special_requests' => $data['special_requests'] ?? null,
            'status'           => 'pending',
            'payment_status'   => 'unpaid',
        ]);
    }

    public function cancel(Booking $booking, ?string $reason = ''): Booking
    {
        if (! $booking->isCancellable()) {
            throw ValidationException::withMessages([
                'status' => ['This booking cannot be cancelled in its current state.'],
            ]);
        }

        $booking->update([
            'status'               => 'cancelled',
            'cancellation_reason'  => $reason ?? '',
            'cancelled_at'         => now(),
        ]);

        return $booking->fresh();
    }

    public function modify(Booking $booking, array $data): Booking
    {
        // Re-check availability excluding the current booking
        $this->availability->assertAvailable(
            $booking->room_id,
            $data['check_in_date'],
            $data['check_out_date'],
            excludeBookingId: $booking->id,
        );

        $nights  = \Carbon\Carbon::parse($data['check_in_date'])
                        ->diffInDays($data['check_out_date']);
        $total   = round($booking->room->price_per_night * $nights, 2);

        $booking->update([
            'check_in_date'  => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'guests_count'   => $data['guests_count'] ?? $booking->guests_count,
            'total_amount'   => $total,
            'final_amount'   => $total - $booking->discount_amount,
        ]);

        return $booking->fresh();
    }
}
