<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OAuthProvider;
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
            'email' => 'test@example.com'
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'name' => 'Test User',
                     'email' => 'test@example.com'
                 ]);
    }

    /** @test */
    public function it_can_logout_with_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Successfully logged out']);
    }

    /** @test */
    public function it_can_refresh_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in'
                 ]);
    }

    /** @test */
    public function it_returns_error_for_invalid_oauth_provider()
    {
        $response = $this->getJson('/api/auth/invalid-provider');

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Invalid provider']);
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

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in',
                     'user'
                 ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        // Verify OAuth provider was created
        $this->assertDatabaseHas('oauth_providers', [
            'provider' => 'google',
            'provider_id' => '123456'
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
            'provider_id' => '123456'
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

        $response = $this->getJson('/api/auth/google/callback');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in',
                     'user'
                 ]);

        // Verify no duplicate user was created
        $this->assertEquals(1, User::where('email', 'test@example.com')->count());
    }
}