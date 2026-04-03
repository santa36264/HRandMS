<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Breakfast
            [
                'name' => 'Scrambled Eggs',
                'description' => 'Fresh scrambled eggs with toast',
                'category' => 'breakfast',
                'price' => 150,
                'preparation_time' => 10,
                'emoji' => '🍳',
                'is_available' => true,
            ],
            [
                'name' => 'Pancakes',
                'description' => 'Fluffy pancakes with syrup and butter',
                'category' => 'breakfast',
                'price' => 200,
                'preparation_time' => 15,
                'emoji' => '🥞',
                'is_available' => true,
            ],
            [
                'name' => 'Oatmeal',
                'description' => 'Warm oatmeal with fruits and honey',
                'category' => 'breakfast',
                'price' => 120,
                'preparation_time' => 8,
                'emoji' => '🥣',
                'is_available' => true,
            ],
            // Main Course
            [
                'name' => 'Grilled Chicken',
                'description' => 'Tender grilled chicken with vegetables',
                'category' => 'main_course',
                'price' => 350,
                'preparation_time' => 25,
                'emoji' => '🍗',
                'is_available' => true,
            ],
            [
                'name' => 'Pasta Carbonara',
                'description' => 'Classic Italian pasta with creamy sauce',
                'category' => 'main_course',
                'price' => 300,
                'preparation_time' => 20,
                'emoji' => '🍝',
                'is_available' => true,
            ],
            [
                'name' => 'Beef Steak',
                'description' => 'Premium beef steak with mashed potatoes',
                'category' => 'main_course',
                'price' => 450,
                'preparation_time' => 30,
                'emoji' => '🥩',
                'is_available' => true,
            ],
            [
                'name' => 'Fish Fillet',
                'description' => 'Fresh fish fillet with lemon sauce',
                'category' => 'main_course',
                'price' => 380,
                'preparation_time' => 25,
                'emoji' => '🐟',
                'is_available' => true,
            ],
            // Drinks
            [
                'name' => 'Orange Juice',
                'description' => 'Fresh squeezed orange juice',
                'category' => 'drinks',
                'price' => 80,
                'preparation_time' => 5,
                'emoji' => '🧃',
                'is_available' => true,
            ],
            [
                'name' => 'Coffee',
                'description' => 'Hot espresso coffee',
                'category' => 'drinks',
                'price' => 100,
                'preparation_time' => 5,
                'emoji' => '☕',
                'is_available' => true,
            ],
            [
                'name' => 'Iced Tea',
                'description' => 'Refreshing iced tea with lemon',
                'category' => 'drinks',
                'price' => 90,
                'preparation_time' => 3,
                'emoji' => '🥤',
                'is_available' => true,
            ],
            // Dessert
            [
                'name' => 'Chocolate Cake',
                'description' => 'Rich chocolate cake with frosting',
                'category' => 'dessert',
                'price' => 180,
                'preparation_time' => 5,
                'emoji' => '🍰',
                'is_available' => true,
            ],
            [
                'name' => 'Ice Cream',
                'description' => 'Vanilla ice cream with toppings',
                'category' => 'dessert',
                'price' => 120,
                'preparation_time' => 2,
                'emoji' => '🍦',
                'is_available' => true,
            ],
            [
                'name' => 'Cheesecake',
                'description' => 'Creamy cheesecake with berry sauce',
                'category' => 'dessert',
                'price' => 200,
                'preparation_time' => 5,
                'emoji' => '🍪',
                'is_available' => true,
            ],
        ];

        foreach ($items as $item) {
            MenuItem::create($item);
        }
    }
}
