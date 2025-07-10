<?php

namespace Tests\Feature;

use App\Models\OAuthProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate');

        // Seed categories
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    /** @test */
    public function it_requires_authentication_for_me_endpoint()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_user_info_when_authenticated()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);
    }

    /** @test */
    public function it_can_logout_with_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_oauth_provider()
    {
        $response = $this->getJson('/api/auth/invalid-provider');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid OAuth provider: Driver [invalid-provider] not supported.',
            ]);
    }

    /** @test */
    public function it_handles_oauth_callback_for_new_user()
    {
        // Mock Socialite
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn('123456');
        $socialiteUser->shouldReceive('getName')->andReturn('Test User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $socialiteUser->shouldReceive('getToken')->andReturn('mock-token');

        $socialiteMock = Mockery::mock('Laravel\Socialite\Contracts\Factory');
        $socialiteMock->shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->app->instance('Laravel\Socialite\Contracts\Factory', $socialiteMock);

        // Set up Google OAuth config for testing
        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        Config::set('app.frontend_url', 'http://localhost:3000');

        $response = $this->get('/api/auth/google/callback');

        $response->assertStatus(302);
        $response->assertRedirect();

        // Check that the redirect URL contains the expected parameters
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/auth/callback', $redirectUrl);
        $this->assertStringContainsString('access_token=', $redirectUrl);
        $this->assertStringContainsString('success=true', $redirectUrl);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Verify OAuth provider was created
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'google',
            'provider_id' => '123456',
        ]);
    }

    /** @test */
    public function it_handles_oauth_callback_for_existing_user()
    {
        // Create existing user and OAuth provider
        $user = User::factory()->create(['email' => 'test@example.com']);
        $oauthProvider = OAuthProvider::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456',
        ]);

        // Mock Socialite
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn('123456');
        $socialiteUser->shouldReceive('getName')->andReturn('Test User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $socialiteUser->shouldReceive('getToken')->andReturn('mock-token');

        $socialiteMock = Mockery::mock('Laravel\Socialite\Contracts\Factory');
        $socialiteMock->shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->app->instance('Laravel\Socialite\Contracts\Factory', $socialiteMock);

        Config::set('services.google', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        Config::set('app.frontend_url', 'http://localhost:3000');

        $response = $this->get('/api/auth/google/callback');

        $response->assertStatus(302);
        $response->assertRedirect();

        // Check that the redirect URL contains the expected parameters
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/auth/callback', $redirectUrl);
        $this->assertStringContainsString('access_token=', $redirectUrl);
        $this->assertStringContainsString('success=true', $redirectUrl);

        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }

    /** @test */
    public function it_returns_token_info_for_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/token-info');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'payload',
                    'expires_at',
                    'issued_at',
                    'user_id',
                ],
            ]);
    }

    /** @test */
    public function it_can_update_user_profile()
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson('/api/auth/me', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_update_profile()
    {
        $response = $this->putJson('/api/auth/me', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_profile_update_data()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Test empty name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson('/api/auth/me', [
            'name' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test too long name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson('/api/auth/me', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
