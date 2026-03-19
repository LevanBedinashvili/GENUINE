<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@genuine-rp.ge'],
            [
                'name' => 'Administrator',
                'email' => 'admin@genuine-rp.ge',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        // Create moderator user
        User::updateOrCreate(
            ['email' => 'moderator@genuine-rp.ge'],
            [
                'name' => 'Moderator',
                'email' => 'moderator@genuine-rp.ge',
                'password' => Hash::make('moderator123'),
                'email_verified_at' => now(),
                'is_admin' => false,
            ]
        );
    }
}
