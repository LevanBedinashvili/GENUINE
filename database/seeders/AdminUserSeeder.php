<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * SECURITY WARNING: 
     * This seeder creates admin users with STRONG RANDOM PASSWORDS.
     * DO NOT hardcode passwords here. Generate secure passwords during deployment.
     * 
     * Execute this seeder ONLY on fresh installation:
     *   php artisan db:seed --class=AdminUserSeeder
     * 
     * After running, securely capture and store the generated passwords.
     * Users MUST change their password on first login.
     */
    public function run(): void
    {
        // Check if admin user already exists to prevent overwriting production data
        if (User::where('email', 'admin@genuine-rp.ge')->exists()) {
            $this->command->warn('Admin user already exists. Skipping seeder to prevent data loss.');
            return;
        }

        // Generate secure random password - CHANGE THIS IMMEDIATELY AFTER SEEDING
        $randomPassword = Str::random(16);

        // Create default admin user with strong random password
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@genuine-rp.ge',
            'password' => Hash::make($randomPassword),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info("✓ Admin user created successfully!");
        $this->command->warn("⚠️  TEMPORARY PASSWORD: {$randomPassword}");
        $this->command->warn("⚠️  User MUST change password immediately after first login!");
        $this->command->warn("⚠️  Securely store this password in your password manager.");
    }
}
