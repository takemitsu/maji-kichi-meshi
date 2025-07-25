<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_test_can_create_review_with_images()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create test images
        $images = [
            UploadedFile::fake()->image('test1.jpg', 1200, 800),
            UploadedFile::fake()->image('test2.png', 800, 600),
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'memo' => 'Test review with images',
            'visited_at' => '2024-01-15',
            'images' => $images,
        ]);

        $response->assertStatus(201);

        $review = Review::latest()->first();
        $this->assertCount(2, $review->images);

        // Check images were processed
        foreach ($review->images as $image) {
            $this->assertNotNull($image->filename);
            $this->assertNotNull($image->thumbnail_path);
            $this->assertNotNull($image->small_path);
            $this->assertNotNull($image->medium_path);
            $this->assertNotNull($image->large_path);

            // Check files exist in storage
            Storage::disk('public')->assertExists($image->thumbnail_path);
            Storage::disk('public')->assertExists($image->small_path);
            Storage::disk('public')->assertExists($image->medium_path);
            Storage::disk('public')->assertExists($image->large_path);
        }
    }

    public function test_test_can_upload_additional_images_to_review()
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $images = [
            UploadedFile::fake()->image('additional1.jpg', 800, 600),
            UploadedFile::fake()->image('additional2.png', 1000, 750),
        ];

        $response = $this->actingAs($user, 'api')->postJson("/api/reviews/{$review->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'uploaded_count',
                'images' => [
                    '*' => ['id', 'urls'],
                ],
            ],
        ]);

        $this->assertCount(2, $review->fresh()->images);
    }

    public function test_test_cannot_upload_more_than_five_images()
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create 3 existing images
        ReviewImage::factory()->count(3)->for($review)->create();

        // Try to upload 3 more images (would exceed limit of 5)
        $images = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg'),
            UploadedFile::fake()->image('test3.jpg'),
        ];

        $response = $this->actingAs($user, 'api')->postJson("/api/reviews/{$review->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Maximum 5 images allowed per review',
        ]);
    }

    public function test_test_can_delete_image_from_review()
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create an image with file paths
        $imageService = new ImageService;
        $testFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'reviews');

        $image = ReviewImage::create([
            'review_id' => $review->id,
            'filename' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'thumbnail_path' => $uploadResult['paths']['thumbnail'],
            'small_path' => $uploadResult['paths']['small'],
            'medium_path' => $uploadResult['paths']['medium'],
            'large_path' => $uploadResult['paths']['large'],
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
        ]);

        $response = $this->actingAs($user, 'api')->deleteJson("/api/reviews/{$review->id}/images/{$image->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Image deleted successfully',
        ]);

        $this->assertDatabaseMissing('review_images', ['id' => $image->id]);

        // Check files were deleted from storage
        Storage::disk('public')->assertMissing($image->thumbnail_path);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->medium_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    public function test_test_unauthorized_user_cannot_upload_images()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        $images = [UploadedFile::fake()->image('test.jpg')];

        $response = $this->actingAs($otherUser, 'api')->postJson("/api/reviews/{$review->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(403);
    }

    public function test_test_unauthorized_user_cannot_delete_images()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($user)->create();
        $image = ReviewImage::factory()->for($review)->create();

        $response = $this->actingAs($otherUser, 'api')->deleteJson("/api/reviews/{$review->id}/images/{$image->id}");

        $response->assertStatus(403);
    }

    public function test_test_validates_image_file_types()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Try to upload a non-image file
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user, 'api')->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-01-15',
            'images' => [$file],
        ]);

        $response->assertStatus(422);
    }

    public function test_test_validates_image_file_size()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create a file larger than 10MB (10240KB)
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(11000);

        $response = $this->actingAs($user, 'api')->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-01-15',
            'images' => [$largeFile],
        ]);

        $response->assertStatus(422);
    }

    public function test_test_image_service_generates_correct_sizes()
    {
        $imageService = new ImageService;
        $testFile = UploadedFile::fake()->image('test.jpg', 1600, 1200);

        $result = $imageService->uploadAndResize($testFile, 'test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertArrayHasKey('thumbnail', $result['paths']);
        $this->assertArrayHasKey('small', $result['paths']);
        $this->assertArrayHasKey('medium', $result['paths']);
        $this->assertArrayHasKey('large', $result['paths']);

        // Check that all files were created
        foreach ($result['paths'] as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_test_review_images_deleted_when_review_deleted()
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create images with actual files
        $imageService = new ImageService;
        $testFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'reviews');

        $image = ReviewImage::create([
            'review_id' => $review->id,
            'filename' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'thumbnail_path' => $uploadResult['paths']['thumbnail'],
            'small_path' => $uploadResult['paths']['small'],
            'medium_path' => $uploadResult['paths']['medium'],
            'large_path' => $uploadResult['paths']['large'],
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
        ]);

        // Delete the review
        $response = $this->actingAs($user, 'api')->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);

        // Check that images were also deleted
        $this->assertDatabaseMissing('review_images', ['id' => $image->id]);

        // Check files were deleted from storage
        Storage::disk('public')->assertMissing($image->thumbnail_path);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->medium_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }
}
