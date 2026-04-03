<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        Booking::factory()->count(20)->create();

        $this->command->info('✓ 20 sample bookings seeded successfully.');
    }
}
