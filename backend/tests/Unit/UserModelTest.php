<?php

namespace Tests\Unit;

use App\Models\OAuthProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_implements_jwt_subject(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Tymon\JWTAuth\Contracts\JWTSubject::class, $user);
        $this->assertEquals($user->id, $user->getJWTIdentifier());
        $this->assertIsArray($user->getJWTCustomClaims());
    }

    public function test_it_can_generate_jwt_token(): void
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_it_has_oauth_providers_relationship(): void
    {
        $user = User::factory()->create();
        $oauthProvider = OAuthProvider::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->oauthProviders()->exists());
        $this->assertEquals(1, $user->oauthProviders()->count());
        $this->assertEquals($oauthProvider->id, $user->oauthProviders()->first()->id);
    }

    public function test_it_has_reviews_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->reviews());
    }

    public function test_it_has_rankings_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->rankings());
    }

    public function test_it_hides_sensitive_attributes(): void
    {
        $user = User::factory()->create([
            'password' => 'secret123',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_it_casts_email_verified_at_to_datetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }
}
