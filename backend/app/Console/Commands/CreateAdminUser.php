<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--email= : Admin email address}
                            {--name= : Admin name}
                            {--password= : Admin password}
                            {--role=admin : User role (admin or moderator)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating admin user...');

        // メールアドレス取得
        $email = $this->option('email') ?: $this->ask('Email address');

        // 既存ユーザーチェック
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists!");

            return Command::FAILURE;
        }

        // 名前取得
        $name = $this->option('name') ?: $this->ask('Full name');

        // パスワード取得
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        // パスワード確認（オプション指定時以外）
        if (!$this->option('password')) {
            $passwordConfirm = $this->secret('Confirm password');
            if ($password !== $passwordConfirm) {
                $this->error('Passwords do not match!');

                return Command::FAILURE;
            }
        }

        // ロール取得
        $role = $this->option('role');
        if (!in_array($role, ['admin', 'moderator'])) {
            $role = $this->choice('Select role', ['admin', 'moderator'], 0);
        }

        // バリデーション
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|min:2|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        // ユーザー作成
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password, // hashedキャストで自動ハッシュ化
                'email_verified_at' => now(),
                'role' => $role,
                'status' => 'active',
            ]);

            $this->info("✅ {$role} user created successfully!");
            $this->table(['Field', 'Value'], [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['Status', $user->status],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create user: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
