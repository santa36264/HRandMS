<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn  = $this->faker->dateTimeBetween('-3 months', '+2 months');
        $checkOut = (clone $checkIn)->modify('+' . $this->faker->numberBetween(1, 14) . ' days');

        $room         = Room::inRandomOrder()->first();
        $user         = User::where('role', 'guest')->inRandomOrder()->first();
        $nights       = (new \DateTime($checkIn->format('Y-m-d')))->diff(new \DateTime($checkOut->format('Y-m-d')))->days;
        $pricePerNight = $room?->price_per_night ?? 99.99;
        $totalAmount  = round($nights * $pricePerNight, 2);
        $discount     = $this->faker->randomElement([0, 0, 0, 10, 20]); // mostly no discount
        $finalAmount  = round($totalAmount - $discount, 2);

        $status = $this->faker->randomElement([
            'pending', 'confirmed', 'confirmed',
            'checked_in', 'checked_out', 'checked_out',
            'cancelled', 'no_show',
        ]);

        $paymentStatus = match ($status) {
            'pending'     => 'unpaid',
            'confirmed'   => $this->faker->randomElement(['unpaid', 'partial', 'paid']),
            'checked_in'  => $this->faker->randomElement(['partial', 'paid']),
            'checked_out' => 'paid',
            'cancelled'   => $this->faker->randomElement(['unpaid', 'refunded']),
            'no_show'     => 'unpaid',
            default       => 'unpaid',
        };

        static $counter = 0;
        $counter++;

        return [
            'booking_reference'  => 'BK-' . now()->format('Ymd') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'user_id'            => $user?->id ?? User::factory(),
            'room_id'            => $room?->id ?? Room::factory(),
            'check_in_date'      => $checkIn->format('Y-m-d'),
            'check_out_date'     => $checkOut->format('Y-m-d'),
            'guests_count'       => $this->faker->numberBetween(1, $room?->capacity ?? 2),
            'total_amount'       => $totalAmount,
            'discount_amount'    => $discount,
            'final_amount'       => $finalAmount,
            'status'             => $status,
            'payment_status'     => $paymentStatus,
            'special_requests'   => $this->faker->optional(0.3)->sentence(),
            'cancellation_reason'=> in_array($status, ['cancelled', 'no_show'])
                                        ? $this->faker->randomElement([
                                            'Change of plans',
                                            'Found a better deal',
                                            'Emergency situation',
                                            'Travel restrictions',
                                          ])
                                        : null,
            'cancelled_at'       => in_array($status, ['cancelled', 'no_show'])
                                        ? $this->faker->dateTimeBetween('-2 months', 'now')
                                        : null,
        ];
    }

    // -----------------------------------------------
    // States
    // -----------------------------------------------
    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'payment_status' => 'unpaid']);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed', 'payment_status' => 'paid']);
    }

    public function checkedIn(): static
    {
        return $this->state(['status' => 'checked_in', 'payment_status' => 'paid']);
    }

    public function checkedOut(): static
    {
        return $this->state(['status' => 'checked_out', 'payment_status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'              => 'cancelled',
            'payment_status'      => 'refunded',
            'cancellation_reason' => 'Cancelled by guest',
            'cancelled_at'        => now(),
        ]);
    }
}
