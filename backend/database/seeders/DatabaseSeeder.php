<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Admin user for Filament
        User::updateOrCreate(
            ['email' => 'takemitsu@notespace.jp'],
            [
                'name' => 'Takemitsu Admin',
                'email' => 'takemitsu@notespace.jp',
                'password' => \Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
