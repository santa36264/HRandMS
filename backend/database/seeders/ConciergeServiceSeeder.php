<?php

namespace Database\Seeders;

use App\Models\ConciergeService;
use Illuminate\Database\Seeder;

class ConciergeServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // Airport Services
            [
                'type' => 'airport',
                'name' => 'Airport Pickup',
                'description' => 'Comfortable pickup from Addis Ababa Bole International Airport',
                'price' => 500,
                'duration_minutes' => 45,
                'provider_name' => 'SATAAB Transport',
                'provider_phone' => '+251911234567',
                'is_available' => true,
            ],
            [
                'type' => 'airport',
                'name' => 'Airport Dropoff',
                'description' => 'Safe dropoff to Addis Ababa Bole International Airport',
                'price' => 500,
                'duration_minutes' => 45,
                'provider_name' => 'SATAAB Transport',
                'provider_phone' => '+251911234567',
                'is_available' => true,
            ],
            // Taxi Services
            [
                'type' => 'taxi',
                'name' => 'City Tour Taxi',
                'description' => 'Explore Addis Ababa with a professional driver',
                'price' => 400,
                'duration_minutes' => 120,
                'provider_name' => 'City Taxi Service',
                'provider_phone' => '+251922345678',
                'is_available' => true,
            ],
            [
                'type' => 'taxi',
                'name' => 'Business District',
                'description' => 'Quick transport to business areas',
                'price' => 250,
                'duration_minutes' => 30,
                'provider_name' => 'City Taxi Service',
                'provider_phone' => '+251922345678',
                'is_available' => true,
            ],
            // Tour Services
            [
                'type' => 'tour',
                'name' => 'Historical Sites Tour',
                'description' => 'Visit St. George Cathedral, National Museum, and more',
                'price' => 800,
                'duration_minutes' => 240,
                'provider_name' => 'Addis Tours',
                'provider_phone' => '+251933456789',
                'is_available' => true,
            ],
            [
                'type' => 'tour',
                'name' => 'Market & Culture Tour',
                'description' => 'Experience local markets and Ethiopian culture',
                'price' => 600,
                'duration_minutes' => 180,
                'provider_name' => 'Addis Tours',
                'provider_phone' => '+251933456789',
                'is_available' => true,
            ],
            // Restaurant Services
            [
                'type' => 'food',
                'name' => 'Traditional Ethiopian',
                'description' => 'Authentic Ethiopian cuisine with injera and doro wot',
                'price' => 350,
                'duration_minutes' => 90,
                'provider_name' => 'Addis Red Restaurant',
                'provider_phone' => '+251944567890',
                'is_available' => true,
            ],
            [
                'type' => 'food',
                'name' => 'Italian Restaurant',
                'description' => 'Fine Italian dining with pasta and seafood',
                'price' => 450,
                'duration_minutes' => 120,
                'provider_name' => 'La Bella Italia',
                'provider_phone' => '+251955678901',
                'is_available' => true,
            ],
            [
                'type' => 'food',
                'name' => 'International Cuisine',
                'description' => 'Mix of international dishes and local favorites',
                'price' => 400,
                'duration_minutes' => 100,
                'provider_name' => 'Global Taste',
                'provider_phone' => '+251966789012',
                'is_available' => true,
            ],
            // Spa Services
            [
                'type' => 'spa',
                'name' => 'Full Body Massage',
                'description' => 'Relaxing 60-minute full body massage',
                'price' => 600,
                'duration_minutes' => 60,
                'provider_name' => 'Serenity Spa',
                'provider_phone' => '+251977890123',
                'is_available' => true,
            ],
            [
                'type' => 'spa',
                'name' => 'Facial Treatment',
                'description' => 'Professional facial with premium products',
                'price' => 400,
                'duration_minutes' => 45,
                'provider_name' => 'Serenity Spa',
                'provider_phone' => '+251977890123',
                'is_available' => true,
            ],
            [
                'type' => 'spa',
                'name' => 'Spa Package',
                'description' => 'Massage + Facial + Sauna access',
                'price' => 1000,
                'duration_minutes' => 150,
                'provider_name' => 'Serenity Spa',
                'provider_phone' => '+251977890123',
                'is_available' => true,
            ],
        ];

        foreach ($services as $service) {
            ConciergeService::create($service);
        }
    }
}
