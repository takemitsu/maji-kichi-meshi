<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ImageManagerをDIコンテナに登録（テスト環境用）
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver);
        });
    }

    public function test_profile_show_requires_authentication(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'profile_image' => null,
                ],
            ]);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_profile_update_validates_email_uniqueness(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->actingAs($user1, 'api')
            ->putJson('/api/profile', [
                'email' => 'user2@example.com',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'profile_image' => [
                        'urls' => [
                            'small',
                            'original',
                        ],
                        'uploaded_at',
                    ],
                ],
            ]);

        // Check user profile image data was saved
        $user->refresh();
        $this->assertNotNull($user->profile_image_filename);
        $this->assertNotNull($user->profile_image_uploaded_at);
    }

    public function test_profile_image_upload_validates_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_profile_image_upload_validates_file_size(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Create a file larger than 10MB (limit is 10MB = 10240KB)
        $file = UploadedFile::fake()->create('large-image.jpg', 11000);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_delete_profile_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // First upload an image
        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);
        $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file,
            ]);

        $user->refresh();
        $this->assertNotNull($user->profile_image_filename);

        // Then delete it
        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/profile/image');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile image deleted successfully',
            ]);

        // Check user profile image data was cleared
        $user->refresh();
        $this->assertNull($user->profile_image_filename);
        $this->assertNull($user->profile_image_uploaded_at);
    }

    public function test_delete_profile_image_fails_when_no_image(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/profile/image');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'No profile image to delete',
            ]);
    }

    public function test_user_can_get_profile_image_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload an image first
        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);
        $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file,
            ]);

        // Get the image URL
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/profile/image-url?size=medium');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'url',
                    'size',
                ],
            ]);
    }

    public function test_get_profile_image_url_fails_when_no_image(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/profile/image-url');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'No profile image found',
            ]);
    }

    public function test_profile_image_upload_replaces_existing_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // Upload first image
        $file1 = UploadedFile::fake()->image('avatar1.jpg', 500, 500);
        $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file1,
            ]);

        $user->refresh();
        $firstFilename = $user->profile_image_filename;

        // Upload second image
        $file2 = UploadedFile::fake()->image('avatar2.jpg', 500, 500);
        $this->actingAs($user, 'api')
            ->postJson('/api/profile/image', [
                'profile_image' => $file2,
            ]);

        $user->refresh();
        $secondFilename = $user->profile_image_filename;

        // Check that filename changed (old image replaced)
        $this->assertNotEquals($firstFilename, $secondFilename);
    }

    public function test_profile_api_requires_authentication(): void
    {
        $response = $this->putJson('/api/profile', [
            'name' => 'Test Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_profile_image_apis_require_authentication(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        // Upload requires auth
        $response = $this->postJson('/api/profile/image', [
            'profile_image' => $file,
        ]);
        $response->assertStatus(401);

        // Delete requires auth
        $response = $this->deleteJson('/api/profile/image');
        $response->assertStatus(401);

        // Get URL requires auth
        $response = $this->getJson('/api/profile/image-url');
        $response->assertStatus(401);
    }
}
