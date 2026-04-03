<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    private static int $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'room_number'     => str_pad(self::$counter, 3, '0', STR_PAD_LEFT),
            'name'            => $this->faker->randomElement(['Deluxe', 'Standard', 'Suite', 'Executive']) . ' Room ' . self::$counter,
            'type'            => $this->faker->randomElement(['single', 'double', 'deluxe', 'suite']),
            'status'          => 'available',
            'floor'           => $this->faker->numberBetween(1, 10),
            'capacity'        => $this->faker->numberBetween(1, 4),
            'price_per_night' => $this->faker->randomFloat(2, 500, 5000),
            'description'     => $this->faker->sentence(),
            'amenities'       => ['wifi', 'ac', 'tv'],
            'images'          => [],
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => 'maintenance']);
    }

    public function withPrice(float $price): static
    {
        return $this->state(['price_per_night' => $price]);
    }
}
