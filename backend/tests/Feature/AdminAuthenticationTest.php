<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用ユーザーを作成
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->moderator = User::factory()->create([
            'name' => 'Moderator User',
            'email' => 'moderator@test.com',
            'role' => 'moderator',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->bannedUser = User::factory()->create([
            'name' => 'Banned User',
            'email' => 'banned@test.com',
            'role' => 'user',
            'status' => 'banned',
        ]);
    }

    public function test_admin_can_access_panel(): void
    {
        $panel = app(\Filament\Panel::class);

        $this->assertTrue($this->admin->canAccessPanel($panel));
    }

    public function test_moderator_can_access_panel(): void
    {
        $panel = app(\Filament\Panel::class);

        $this->assertTrue($this->moderator->canAccessPanel($panel));
    }

    public function test_regular_user_cannot_access_panel(): void
    {
        $panel = app(\Filament\Panel::class);

        $this->assertFalse($this->user->canAccessPanel($panel));
    }

    public function test_banned_user_cannot_access_panel(): void
    {
        $panel = app(\Filament\Panel::class);

        $this->assertFalse($this->bannedUser->canAccessPanel($panel));
    }

    public function test_unauthenticated_user_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_has_proper_role_permissions(): void
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->isModerator());
        $this->assertTrue($this->admin->isActive());
    }

    public function test_moderator_has_proper_role_permissions(): void
    {
        $this->assertFalse($this->moderator->isAdmin());
        $this->assertTrue($this->moderator->isModerator());
        $this->assertTrue($this->moderator->isActive());
    }

    public function test_regular_user_has_proper_role_permissions(): void
    {
        $this->assertFalse($this->user->isAdmin());
        $this->assertFalse($this->user->isModerator());
        $this->assertTrue($this->user->isActive());
    }

    public function test_banned_user_has_proper_role_permissions(): void
    {
        $this->assertFalse($this->bannedUser->isAdmin());
        $this->assertFalse($this->bannedUser->isModerator());
        $this->assertFalse($this->bannedUser->isActive());
    }

    public function test_user_role_defaults_are_correct(): void
    {
        $defaultUser = \App\Models\User::factory()->create();

        $this->assertEquals('user', $defaultUser->role);
        $this->assertEquals('active', $defaultUser->status);
        $this->assertFalse($defaultUser->isAdmin());
        $this->assertFalse($defaultUser->isModerator());
        $this->assertTrue($defaultUser->isActive());
    }
}
