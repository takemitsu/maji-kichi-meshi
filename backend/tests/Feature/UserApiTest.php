<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_info()
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->getJson("/api/users/{$user->id}/info");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
            ])
            ->assertJsonMissing([
                'email' => $user->email, // プライベート情報は除外
            ]);
    }

    public function test_returns_404_for_nonexistent_user()
    {
        $response = $this->getJson('/api/users/99999/info');

        $response->assertStatus(404);
    }
}
