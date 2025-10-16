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

        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_it_requires_authentication_for_me_endpoint(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_it_returns_user_info_when_authenticated(): void
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

    public function test_it_can_logout_with_valid_token(): void
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

    public function test_it_returns_error_for_invalid_oauth_provider(): void
    {
        $response = $this->getJson('/api/auth/invalid-provider');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid OAuth provider: Driver [invalid-provider] not supported.',
            ]);
    }

    public function test_it_handles_oauth_callback_for_new_user(): void
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

    public function test_it_handles_oauth_callback_for_existing_user(): void
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

    public function test_it_returns_token_info_for_authenticated_user(): void
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

    public function test_it_can_update_user_profile(): void
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

    public function test_it_requires_authentication_to_update_profile(): void
    {
        $response = $this->putJson('/api/auth/me', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_validates_profile_update_data(): void
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

    public function test_oauth_callback_handles_socialite_exception(): void
    {
        // Mock Socialite to throw exception
        $providerMock = Mockery::mock();
        /** @phpstan-ignore-next-line */
        $providerMock->shouldReceive('user')->andThrow(new \Exception('OAuth provider error'));

        $socialiteMock = Mockery::mock('Laravel\Socialite\Contracts\Factory');
        $socialiteMock->shouldReceive('driver')->andReturn($providerMock);

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

        // Check error callback URL
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/auth/callback', $redirectUrl);
        $this->assertStringContainsString('error=oauth_failed', $redirectUrl);
        $this->assertStringContainsString('success=false', $redirectUrl);
    }

    public function test_logout_requires_authentication(): void
    {
        // Test logout without providing a token (should require auth)
        $response = $this->postJson('/api/auth/logout');

        // Should return 401 unauthorized
        $response->assertStatus(401);
    }

    public function test_multiple_oauth_providers_for_same_user(): void
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'multi@example.com',
            'name' => 'Multi Auth User',
        ]);

        // Link first OAuth provider (Google)
        $googleProvider = OAuthProvider::factory()->create([
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        // Mock second OAuth provider (GitHub) with same email
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn('github-456');
        $socialiteUser->shouldReceive('getName')->andReturn('Multi Auth User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('multi@example.com');
        $socialiteUser->shouldReceive('getToken')->andReturn('mock-token');

        $socialiteMock = Mockery::mock('Laravel\Socialite\Contracts\Factory');
        $socialiteMock->shouldReceive('driver->user')->andReturn($socialiteUser);

        $this->app->instance('Laravel\Socialite\Contracts\Factory', $socialiteMock);

        Config::set('services.github', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'http://localhost/auth/github/callback',
        ]);

        Config::set('app.frontend_url', 'http://localhost:3000');

        $response = $this->get('/api/auth/github/callback');

        $response->assertStatus(302);

        // Verify both OAuth providers exist for the same user
        $this->assertEquals(2, OAuthProvider::where('user_id', $existingUser->id)->count());
        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'github',
            'provider_id' => 'github-456',
        ]);
    }

    public function test_update_profile_with_empty_request_body(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $token = JWTAuth::fromUser($user);

        // Try to update without providing any data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->putJson('/api/auth/me', []);

        // Should fail validation since 'name' is required
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_token_info_requires_valid_token(): void
    {
        // Test without token
        $response = $this->getJson('/api/auth/token-info');

        $response->assertStatus(401);

        // Test with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-string',
            'Accept' => 'application/json',
        ])->getJson('/api/auth/token-info');

        $response->assertStatus(401);
    }

    public function test_oauth_callback_links_existing_user_by_email(): void
    {
        // Create existing user without OAuth provider
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        // Mock Socialite with same email but new OAuth provider
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn('new-oauth-id-999');
        $socialiteUser->shouldReceive('getName')->andReturn('OAuth Name');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
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

        // Check success callback URL
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString('http://localhost:3000/auth/callback', $redirectUrl);
        $this->assertStringContainsString('access_token=', $redirectUrl);
        $this->assertStringContainsString('success=true', $redirectUrl);

        // Verify no duplicate user was created (still only 1 user)
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());

        // Verify new OAuth provider was linked to existing user
        $this->assertDatabaseHas('oauth_providers', [
            'user_id' => $existingUser->id,
            'provider' => 'google',
            'provider_id' => 'new-oauth-id-999',
        ]);
    }
}
