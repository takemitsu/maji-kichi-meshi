<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者ユーザーを作成
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@majikichi-meshi.com',
            'password' => 'admin123',
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // モデレーターユーザーを作成
        User::create([
            'name' => 'Content Moderator',
            'email' => 'moderator@majikichi-meshi.com',
            'password' => 'moderator123',
            'email_verified_at' => now(),
            'role' => 'moderator',
            'status' => 'active',
        ]);

        // 開発用の管理者ユーザー
        if (app()->environment('local')) {
            User::create([
                'name' => 'Dev Admin',
                'email' => 'dev@example.com',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'admin',
                'status' => 'active',
            ]);
        }

        $this->command->info('Admin users created successfully.');
    }
}
