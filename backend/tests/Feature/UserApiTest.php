<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_user_info(): void
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

    public function test_returns_404_for_nonexistent_user(): void
    {
        $response = $this->getJson('/api/users/99999/info');

        $response->assertStatus(404);
    }

    public function test_can_get_user_info_with_profile_image(): void
    {
        $user = User::factory()->create([
            'name' => 'User With Image',
            'profile_image_filename' => 'test-profile.jpg',
            'profile_image_small_path' => 'profile-images/test-profile-small.jpg',
            'profile_image_uploaded_at' => now(),
        ]);

        $response = $this->getJson("/api/users/{$user->id}/info");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'User With Image',
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'created_at',
                'profile_image' => [
                    'urls' => [
                        'small',
                        'original',
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertNotNull($data['profile_image']);
        $this->assertArrayHasKey('urls', $data['profile_image']);
        $this->assertArrayHasKey('small', $data['profile_image']['urls']);
        $this->assertArrayHasKey('original', $data['profile_image']['urls']);
    }

    public function test_user_without_profile_image_returns_null(): void
    {
        $user = User::factory()->create([
            'name' => 'User Without Image',
            'profile_image_filename' => null,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/info");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'User Without Image',
                'profile_image' => null,
            ]);
    }
}
