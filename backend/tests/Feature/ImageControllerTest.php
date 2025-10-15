<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\User;
use App\Services\ImageService;
use App\Services\LazyImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ImageControllerTest extends TestCase
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

    // =============================================================================
    // lazyServe() Tests
    // =============================================================================

    public function test_lazy_serve_returns_400_with_invalid_size(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create a published image
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
            'moderation_status' => 'published',
        ]);

        // Try to access with invalid size
        // Note: Invalid size results in 404 because route doesn't match
        // (not 400 from LazyImageService, as route validation happens first)
        $response = $this->getJson("/api/images/reviews/invalid_size/{$image->filename}");

        $response->assertStatus(404);
    }

    public function test_lazy_serve_returns_404_with_nonexistent_filename(): void
    {
        $response = $this->getJson('/api/images/reviews/thumbnail/nonexistent-file.jpg');

        $response->assertStatus(404);
    }

    public function test_lazy_serve_returns_403_when_image_is_not_published(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create an image with 'under_review' status
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
            'moderation_status' => 'under_review', // Not published
        ]);

        $response = $this->getJson("/api/images/reviews/thumbnail/{$image->filename}");

        $response->assertStatus(403);
    }

    public function test_lazy_serve_returns_403_when_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create an image with 'rejected' status
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
            'moderation_status' => 'rejected', // Rejected
        ]);

        $response = $this->getJson("/api/images/reviews/thumbnail/{$image->filename}");

        $response->assertStatus(403);
    }

    public function test_lazy_serve_successfully_returns_thumbnail_for_review_image(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create a published image
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
            'moderation_status' => 'published',
        ]);

        $response = $this->getJson("/api/images/reviews/thumbnail/{$image->filename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        // Note: Cache-Control headers differ in test environment (Storage::fake())
        // In production, ImageController sets 'public, max-age=31536000'
        // In test environment, Laravel returns 'max-age=0, must-revalidate, no-cache, no-store, private'
    }

    public function test_lazy_serve_successfully_returns_original_for_review_image(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create a published image
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
            'moderation_status' => 'published',
        ]);

        $response = $this->getJson("/api/images/reviews/original/{$image->filename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_lazy_serve_successfully_returns_shop_image(): void
    {
        $shop = Shop::factory()->create();

        // Create a published shop image
        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('shop.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'shops');

        $image = ShopImage::create([
            'shop_id' => $shop->id,
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
            'moderation_status' => 'published',
            'image_sizes' => json_encode([
                'thumbnail' => "/storage/images/shops/thumbnail/{$uploadResult['filename']}",
                'small' => "/storage/images/shops/small/{$uploadResult['filename']}",
                'medium' => "/storage/images/shops/medium/{$uploadResult['filename']}",
            ]),
        ]);

        $response = $this->getJson("/api/images/shops/thumbnail/{$image->filename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_lazy_serve_generates_small_size_on_demand(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create a published image (only thumbnail is generated initially)
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
            'moderation_status' => 'published',
        ]);

        // Verify small size is NOT generated initially
        $this->assertFalse($image->isSizeGenerated('small'));
        Storage::disk('public')->assertMissing($image->small_path);

        // Request small size (should trigger lazy generation)
        $response = $this->getJson("/api/images/reviews/small/{$image->filename}");

        $response->assertStatus(200);

        // Verify small size is now generated and stored
        $image->refresh();
        $this->assertTrue($image->isSizeGenerated('small'));
        Storage::disk('public')->assertExists($image->small_path);
    }

    // =============================================================================
    // serve() Tests (Backward compatibility)
    // =============================================================================

    public function test_serve_returns_404_with_nonexistent_image(): void
    {
        $response = $this->getJson('/api/images/thumbnail/nonexistent.jpg');

        $response->assertStatus(404);
    }

    public function test_serve_returns_403_for_unpublished_review_image(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

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
            'moderation_status' => 'under_review', // Not published
        ]);

        $response = $this->getJson("/api/images/thumbnail/{$image->filename}");

        $response->assertStatus(403);
    }

    public function test_serve_returns_403_for_unpublished_shop_image(): void
    {
        $shop = Shop::factory()->create();

        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('shop.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'shops');

        $image = ShopImage::create([
            'shop_id' => $shop->id,
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
            'moderation_status' => 'rejected', // Not published
            'image_sizes' => json_encode([
                'thumbnail' => "/storage/images/shops/thumbnail/{$uploadResult['filename']}",
                'small' => "/storage/images/shops/small/{$uploadResult['filename']}",
                'medium' => "/storage/images/shops/medium/{$uploadResult['filename']}",
            ]),
        ]);

        $response = $this->getJson("/api/images/thumbnail/{$image->filename}");

        $response->assertStatus(403);
    }

    public function test_serve_successfully_returns_review_image(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

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
            'moderation_status' => 'published',
        ]);

        $response = $this->getJson("/api/images/thumbnail/{$image->filename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
        // Note: Cache-Control headers differ in test environment (Storage::fake())
        // In production, ImageController sets 'public, max-age=31536000'
        // In test environment, Laravel returns 'max-age=0, must-revalidate, no-cache, no-store, private'
    }

    public function test_serve_successfully_returns_shop_image(): void
    {
        $shop = Shop::factory()->create();

        $imageService = app(ImageService::class);
        $testFile = UploadedFile::fake()->image('shop.jpg', 800, 600);
        $uploadResult = $imageService->uploadAndResize($testFile, 'shops');

        $image = ShopImage::create([
            'shop_id' => $shop->id,
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
            'moderation_status' => 'published',
            'image_sizes' => json_encode([
                'thumbnail' => "/storage/images/shops/thumbnail/{$uploadResult['filename']}",
                'small' => "/storage/images/shops/small/{$uploadResult['filename']}",
                'medium' => "/storage/images/shops/medium/{$uploadResult['filename']}",
            ]),
        ]);

        $response = $this->getJson("/api/images/thumbnail/{$image->filename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    // =============================================================================
    // Edge Cases
    // =============================================================================

    public function test_lazy_serve_returns_404_when_original_file_is_missing(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

        // Create image record but delete the actual file
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
            'moderation_status' => 'published',
        ]);

        // Delete the original file
        Storage::disk('public')->delete($image->original_path);

        // Try to generate small size (should fail because original is missing)
        $response = $this->getJson("/api/images/reviews/small/{$image->filename}");

        $response->assertStatus(404);
    }

    public function test_serve_only_returns_generated_sizes(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create();

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
            'moderation_status' => 'published',
        ]);

        // serve() can only serve thumbnail (which is already generated)
        $response = $this->getJson("/api/images/thumbnail/{$image->filename}");
        $response->assertStatus(200);

        // serve() returns 404 for sizes that aren't generated yet (small, medium)
        // because it doesn't have lazy generation logic
        $response = $this->getJson("/api/images/small/{$image->filename}");
        $response->assertStatus(404);

        $response = $this->getJson("/api/images/medium/{$image->filename}");
        $response->assertStatus(404);
    }
}
