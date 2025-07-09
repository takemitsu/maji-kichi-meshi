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
        // 基本的なシーダーを順番に実行
        $this->call([
            CategorySeeder::class,
            AdminSeeder::class,
            ShopSeeder::class,
            ReviewSeeder::class,
            RankingSeeder::class,
        ]);

        // 既存のテストユーザーとTakemitsuユーザーを維持
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
                'password' => \Hash::make('admin2024!'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Database seeding completed successfully!');
    }
}
