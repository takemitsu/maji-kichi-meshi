<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
            'status' => 'active'
        ]);
        
        $this->moderator = User::factory()->create([
            'name' => 'Moderator User',
            'email' => 'moderator@test.com',
            'role' => 'moderator',
            'status' => 'active'
        ]);
        
        $this->user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'role' => 'user',
            'status' => 'active'
        ]);
        
        $this->bannedUser = User::factory()->create([
            'name' => 'Banned User',
            'email' => 'banned@test.com',
            'role' => 'user',
            'status' => 'banned'
        ]);
    }

    /** @test */
    public function admin_can_access_panel()
    {
        $panel = app(\Filament\Panel::class);
        
        $this->assertTrue($this->admin->canAccessPanel($panel));
    }

    /** @test */
    public function moderator_can_access_panel()
    {
        $panel = app(\Filament\Panel::class);
        
        $this->assertTrue($this->moderator->canAccessPanel($panel));
    }

    /** @test */
    public function regular_user_cannot_access_panel()
    {
        $panel = app(\Filament\Panel::class);
        
        $this->assertFalse($this->user->canAccessPanel($panel));
    }

    /** @test */
    public function banned_user_cannot_access_panel()
    {
        $panel = app(\Filament\Panel::class);
        
        $this->assertFalse($this->bannedUser->canAccessPanel($panel));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_panel()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function admin_has_proper_role_permissions()
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->isModerator());
        $this->assertTrue($this->admin->isActive());
    }

    /** @test */
    public function moderator_has_proper_role_permissions()
    {
        $this->assertFalse($this->moderator->isAdmin());
        $this->assertTrue($this->moderator->isModerator());
        $this->assertTrue($this->moderator->isActive());
    }

    /** @test */
    public function regular_user_has_proper_role_permissions()
    {
        $this->assertFalse($this->user->isAdmin());
        $this->assertFalse($this->user->isModerator());
        $this->assertTrue($this->user->isActive());
    }

    /** @test */
    public function banned_user_has_proper_role_permissions()
    {
        $this->assertFalse($this->bannedUser->isAdmin());
        $this->assertFalse($this->bannedUser->isModerator());
        $this->assertFalse($this->bannedUser->isActive());
    }

    /** @test */
    public function user_role_defaults_are_correct()
    {
        $defaultUser = \App\Models\User::factory()->create();
        
        $this->assertEquals('user', $defaultUser->role);
        $this->assertEquals('active', $defaultUser->status);
        $this->assertFalse($defaultUser->isAdmin());
        $this->assertFalse($defaultUser->isModerator());
        $this->assertTrue($defaultUser->isActive());
    }
}