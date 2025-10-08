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
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // ImageManagerをDIコンテナに登録（テスト環境用）
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver);
        });
    }

    public function test_test_can_create_review_with_images(): void
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
            $this->assertNotNull($image->original_path);
            $this->assertNotNull($image->sizes_generated);

            // Check thumbnail is generated immediately, others are not
            $this->assertTrue($image->isSizeGenerated('thumbnail'));
            $this->assertFalse($image->isSizeGenerated('small'));
            $this->assertFalse($image->isSizeGenerated('medium'));

            // Check only thumbnail and original files exist in storage
            Storage::disk('public')->assertExists($image->thumbnail_path);
            Storage::disk('public')->assertExists($image->original_path);
            Storage::disk('public')->assertMissing($image->small_path);
            Storage::disk('public')->assertMissing($image->medium_path);
        }
    }

    public function test_test_can_upload_additional_images_to_review(): void
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

    public function test_test_cannot_upload_more_than_five_images(): void
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
            'error' => 'Maximum 5 images allowed',
        ]);
    }

    public function test_test_can_delete_image_from_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create an image with file paths
        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'reviews');

        $image = ReviewImage::create([
            'review_id' => $review->id,
            'uuid' => $uploadResult['uuid'],
            'filename' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'thumbnail_path' => $uploadResult['paths']['thumbnail'],
            'small_path' => $uploadResult['paths']['small'],
            'medium_path' => $uploadResult['paths']['medium'],
            'original_path' => $uploadResult['original_path'],
            'sizes_generated' => $uploadResult['sizes_generated'],
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
        Storage::disk('public')->assertMissing($image->original_path);
        // small and medium may not exist if they were never generated
        if ($image->isSizeGenerated('small')) {
            Storage::disk('public')->assertMissing($image->small_path);
        }
        if ($image->isSizeGenerated('medium')) {
            Storage::disk('public')->assertMissing($image->medium_path);
        }
    }

    public function test_test_unauthorized_user_cannot_upload_images(): void
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

    public function test_test_unauthorized_user_cannot_delete_images(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->for($user)->create();
        $image = ReviewImage::factory()->for($review)->create();

        $response = $this->actingAs($otherUser, 'api')->deleteJson("/api/reviews/{$review->id}/images/{$image->id}");

        $response->assertStatus(403);
    }

    public function test_test_validates_image_file_types(): void
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

    public function test_test_validates_image_file_size(): void
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

    public function test_test_image_service_generates_correct_sizes(): void
    {
        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('test.jpg', 1600, 1200);

        $result = $imageService->uploadAndResize($testFile, 'test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertArrayHasKey('thumbnail', $result['paths']);
        $this->assertArrayHasKey('small', $result['paths']);
        $this->assertArrayHasKey('medium', $result['paths']);
        $this->assertArrayHasKey('original_path', $result);
        $this->assertArrayHasKey('sizes_generated', $result);

        // Check that only thumbnail and original files were created
        Storage::disk('public')->assertExists($result['paths']['thumbnail']);
        Storage::disk('public')->assertExists($result['original_path']);
        Storage::disk('public')->assertMissing($result['paths']['small']);
        Storage::disk('public')->assertMissing($result['paths']['medium']);

        // Check that only thumbnail is marked as generated
        $this->assertTrue($result['sizes_generated']['thumbnail']);
        $this->assertFalse($result['sizes_generated']['small'] ?? true);
        $this->assertFalse($result['sizes_generated']['medium'] ?? true);
    }

    public function test_test_review_images_deleted_when_review_deleted(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create images with actual files
        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'reviews');

        $image = ReviewImage::create([
            'review_id' => $review->id,
            'uuid' => $uploadResult['uuid'],
            'filename' => $uploadResult['filename'],
            'original_name' => $uploadResult['original_name'],
            'thumbnail_path' => $uploadResult['paths']['thumbnail'],
            'small_path' => $uploadResult['paths']['small'],
            'medium_path' => $uploadResult['paths']['medium'],
            'original_path' => $uploadResult['original_path'],
            'sizes_generated' => $uploadResult['sizes_generated'],
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
        Storage::disk('public')->assertMissing($image->original_path);
        // small and medium may not exist if they were never generated
        if ($image->isSizeGenerated('small')) {
            Storage::disk('public')->assertMissing($image->small_path);
        }
        if ($image->isSizeGenerated('medium')) {
            Storage::disk('public')->assertMissing($image->medium_path);
        }
    }

    public function test_multiple_reviews_with_images_for_same_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create first review with images
        $image1 = UploadedFile::fake()->image('first-visit.jpg', 100, 100);
        $image2 = UploadedFile::fake()->image('first-visit-2.jpg', 100, 100);

        $response1 = $this->actingAs($user, 'api')->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-01-01',
            'memo' => 'First visit',
            'images' => [$image1, $image2],
        ]);

        $response1->assertStatus(201);
        $firstReviewId = $response1->json('data.id');

        // Create second review with different images
        $image3 = UploadedFile::fake()->image('second-visit.jpg', 100, 100);
        $image4 = UploadedFile::fake()->image('second-visit-2.jpg', 100, 100);
        $image5 = UploadedFile::fake()->image('second-visit-3.jpg', 100, 100);

        $response2 = $this->actingAs($user, 'api')->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 5,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-02-01',
            'memo' => 'Second visit',
            'images' => [$image3, $image4, $image5],
        ]);

        $response2->assertStatus(201);
        $secondReviewId = $response2->json('data.id');

        // Verify both reviews exist with their respective images
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseCount('review_images', 5); // 2 + 3 images

        // Verify first review has 2 images
        $firstReviewImages = ReviewImage::where('review_id', $firstReviewId)->count();
        $this->assertEquals(2, $firstReviewImages);

        // Verify second review has 3 images
        $secondReviewImages = ReviewImage::where('review_id', $secondReviewId)->count();
        $this->assertEquals(3, $secondReviewImages);

        // Verify both reviews are for the same shop but different
        $this->assertDatabaseHas('reviews', [
            'id' => $firstReviewId,
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'memo' => 'First visit',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $secondReviewId,
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'memo' => 'Second visit',
        ]);
    }
}
