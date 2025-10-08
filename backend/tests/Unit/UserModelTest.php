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

    public function test_get_two_factor_qr_code_url_throws_exception_when_secret_not_set(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Two-factor secret not set');

        $user->getTwoFactorQrCodeUrl();
    }

    public function test_verify_two_factor_code_returns_false_when_secret_not_set(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => null,
        ]);

        $result = $user->verifyTwoFactorCode('123456');

        $this->assertFalse($result);
    }

    public function test_delete_profile_image_clears_all_fields(): void
    {
        $user = User::factory()->create([
            'profile_image_filename' => 'test.jpg',
            'profile_image_original_name' => 'original.jpg',
            'profile_image_thumbnail_path' => 'path/thumbnail.jpg',
            'profile_image_small_path' => 'path/small.jpg',
            'profile_image_medium_path' => 'path/medium.jpg',
            'profile_image_large_path' => 'path/large.jpg',
            'profile_image_file_size' => 1024,
            'profile_image_mime_type' => 'image/jpeg',
            'profile_image_uploaded_at' => now(),
        ]);

        $user->deleteProfileImage();

        $this->assertNull($user->profile_image_filename);
        $this->assertNull($user->profile_image_original_name);
        $this->assertNull($user->profile_image_thumbnail_path);
        $this->assertNull($user->profile_image_small_path);
        $this->assertNull($user->profile_image_medium_path);
        $this->assertNull($user->profile_image_large_path);
        $this->assertNull($user->profile_image_file_size);
        $this->assertNull($user->profile_image_mime_type);
        $this->assertNull($user->profile_image_uploaded_at);
    }

    public function test_delete_profile_image_does_nothing_when_no_image(): void
    {
        $user = User::factory()->create([
            'profile_image_filename' => null,
        ]);

        // Should not throw exception
        $user->deleteProfileImage();

        $this->assertNull($user->profile_image_filename);
    }
}
