<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin User',
                'email'    => 'admin@hrms.com',
                'phone'    => '0911000001',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Staff Member',
                'email'    => 'staff@hrms.com',
                'phone'    => '0911000002',
                'password' => Hash::make('password'),
                'role'     => 'staff',
            ],
            [
                'name'     => 'Guest User',
                'email'    => 'guest@hrms.com',
                'phone'    => '0900123456',
                'password' => Hash::make('password'),
                'role'     => 'guest',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }

        $this->command->info('✓ ' . count($users) . ' users seeded successfully.');
    }
}
