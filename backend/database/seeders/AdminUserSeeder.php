<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@uffizi-tickets.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'), // Change this in production!
            ]
        );

        $this->command->info('Admin user created: admin@uffizi-tickets.com');
        $this->command->warn('Default password: admin123 - Please change this immediately!');
    }
}
