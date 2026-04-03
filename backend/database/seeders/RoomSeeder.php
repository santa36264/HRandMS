<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [

            // ── Floor 1: Single Rooms ──────────────────────────
            [
                'room_number'     => '101',
                'name'            => 'Cozy Single',
                'type'            => 'single',
                'status'          => 'available',
                'floor'           => 1,
                'capacity'        => 1,
                'price_per_night' => 1800.00,
                'description'     => 'A comfortable single room with a city view, ideal for solo travelers.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom'],
                'images'          => ['https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '102',
                'name'            => 'Standard Single',
                'type'            => 'single',
                'status'          => 'available',
                'floor'           => 1,
                'capacity'        => 1,
                'price_per_night' => 1500.00,
                'description'     => 'A clean and simple single room with all essential amenities.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning'],
                'images'          => ['https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '103',
                'name'            => 'Garden Single',
                'type'            => 'single',
                'status'          => 'maintenance',
                'floor'           => 1,
                'capacity'        => 1,
                'price_per_night' => 2000.00,
                'description'     => 'Single room with a relaxing garden view.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'garden_view'],
                'images'          => ['https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800&q=80'],
                'is_active'       => true,
            ],

            // ── Floor 2: Double Rooms ──────────────────────────
            [
                'room_number'     => '201',
                'name'            => 'Classic Double',
                'type'            => 'double',
                'status'          => 'available',
                'floor'           => 2,
                'capacity'        => 2,
                'price_per_night' => 2800.00,
                'description'     => 'Spacious double room perfect for couples or friends.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar'],
                'images'          => ['https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '202',
                'name'            => 'Superior Double',
                'type'            => 'double',
                'status'          => 'available',
                'floor'           => 2,
                'capacity'        => 2,
                'price_per_night' => 3200.00,
                'description'     => 'Superior double room with a king-size bed and city view.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe'],
                'images'          => ['https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '203',
                'name'            => 'Twin Double',
                'type'            => 'double',
                'status'          => 'occupied',
                'floor'           => 2,
                'capacity'        => 2,
                'price_per_night' => 2600.00,
                'description'     => 'Double room with two single beds, great for friends or colleagues.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom'],
                'images'          => ['https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80'],
                'is_active'       => true,
            ],

            // ── Floor 3: Deluxe Rooms ──────────────────────────
            [
                'room_number'     => '301',
                'name'            => 'Deluxe King',
                'type'            => 'deluxe',
                'status'          => 'available',
                'floor'           => 3,
                'capacity'        => 2,
                'price_per_night' => 4500.00,
                'description'     => 'Deluxe room with a king-size bed, premium furnishings, and panoramic views.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe', 'bathtub', 'balcony'],
                'images'          => ['https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '302',
                'name'            => 'Deluxe Family',
                'type'            => 'deluxe',
                'status'          => 'available',
                'floor'           => 3,
                'capacity'        => 4,
                'price_per_night' => 5500.00,
                'description'     => 'Spacious deluxe room designed for families with extra beds available.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe'],
                'images'          => ['https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&q=80'],
                'is_active'       => true,
            ],

            // ── Floor 4: Suites ────────────────────────────────
            [
                'room_number'     => '401',
                'name'            => 'Junior Suite',
                'type'            => 'suite',
                'status'          => 'available',
                'floor'           => 4,
                'capacity'        => 2,
                'price_per_night' => 7500.00,
                'description'     => 'Elegant junior suite with a separate living area and premium amenities.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe', 'bathtub', 'balcony'],
                'images'          => ['https://images.unsplash.com/photo-1591088398332-8a7791972843?w=800&q=80'],
                'is_active'       => true,
            ],
            [
                'room_number'     => '402',
                'name'            => 'Executive Suite',
                'type'            => 'suite',
                'status'          => 'available',
                'floor'           => 4,
                'capacity'        => 3,
                'price_per_night' => 10500.00,
                'description'     => 'Luxurious executive suite with a private lounge, workspace, and city skyline view.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe', 'bathtub', 'balcony', 'nespresso'],
                'images'          => ['https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80'],
                'is_active'       => true,
            ],

            // ── Floor 5: Penthouse ─────────────────────────────
            [
                'room_number'     => '501',
                'name'            => 'Royal Penthouse',
                'type'            => 'penthouse',
                'status'          => 'available',
                'floor'           => 5,
                'capacity'        => 4,
                'price_per_night' => 18000.00,
                'description'     => 'The crown jewel of the hotel. A full-floor penthouse with private terrace, jacuzzi, and butler service.',
                'amenities'       => ['wifi', 'tv', 'air_conditioning', 'private_bathroom', 'minibar', 'safe', 'jacuzzi', 'kitchen', 'nespresso'],
                'images'          => ['https://images.unsplash.com/photo-1602002418082-a4443e081dd1?w=800&q=80'],
                'is_active'       => true,
            ],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['room_number' => $room['room_number']],
                $room
            );
        }

        $this->command->info('✓ ' . count($rooms) . ' rooms seeded successfully.');
    }
}
