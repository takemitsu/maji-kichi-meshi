<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\User;
use App\Services\LazyImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LazyImageGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Shop $shop;

    private Review $review;

    private LazyImageService $lazyImageService;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のストレージ設定
        Storage::fake('public');

        // ImageManagerをDIコンテナに登録（テスト環境用）
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver);
        });

        $this->lazyImageService = app(LazyImageService::class);

        // テストデータ作成
        $this->user = User::factory()->create();
        $this->shop = Shop::factory()->create();
        $this->review = Review::factory()->create([
            'user_id' => $this->user->id,
            'shop_id' => $this->shop->id,
        ]);
    }

    #[Test]
    public function it_can_serve_original_image()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成（新しい遅延生成対応版）
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // オリジナル画像のパスを取得
        $originalPath = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'original');

        $this->assertNotNull($originalPath);
        $this->assertTrue(Storage::disk('public')->exists($originalPath));
    }

    #[Test]
    public function it_generates_thumbnail_immediately_during_upload()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // thumbnailが即座に生成されているか確認
        $this->assertNotNull($reviewImage->sizes_generated);
        $this->assertTrue($reviewImage->sizes_generated['thumbnail'] ?? false);
        $this->assertFalse($reviewImage->sizes_generated['small'] ?? true);
        $this->assertFalse($reviewImage->sizes_generated['medium'] ?? true);

        // thumbnailファイルが存在するか確認
        $this->assertTrue(Storage::disk('public')->exists($reviewImage->thumbnail_path));
    }

    #[Test]
    public function it_generates_small_and_medium_sizes_on_demand()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // 初期状態では small と medium は生成されていない
        $this->assertFalse($reviewImage->isSizeGenerated('small'));
        $this->assertFalse($reviewImage->isSizeGenerated('medium'));

        // smallサイズを要求
        $smallPath = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'small');

        $this->assertNotNull($smallPath);
        $this->assertTrue(Storage::disk('public')->exists($smallPath));

        // データベースで生成済みフラグが更新されているか確認
        $reviewImage->refresh();
        $this->assertTrue($reviewImage->isSizeGenerated('small'));

        // mediumサイズを要求
        $mediumPath = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'medium');

        $this->assertNotNull($mediumPath);
        $this->assertTrue(Storage::disk('public')->exists($mediumPath));

        // データベースで生成済みフラグが更新されているか確認
        $reviewImage->refresh();
        $this->assertTrue($reviewImage->isSizeGenerated('medium'));
    }

    #[Test]
    public function it_returns_existing_path_for_already_generated_sizes()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // 最初のsmallサイズ生成
        $firstPath = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'small');

        // 2回目の要求で同じパスが返されるか確認
        $secondPath = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'small');

        $this->assertEquals($firstPath, $secondPath);
    }

    #[Test]
    public function it_returns_null_for_unsupported_sizes()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // サポートされていないサイズを要求
        $path = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'unsupported');

        $this->assertNull($path);
    }

    #[Test]
    public function it_handles_missing_original_image()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // オリジナル画像を削除
        Storage::disk('public')->delete($reviewImage->original_path);

        // smallサイズ生成を試行
        $path = $this->lazyImageService->generateImageIfNeeded($reviewImage, 'small');

        $this->assertNull($path);
    }

    #[Test]
    public function api_endpoint_serves_images_correctly()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // thumbnailはすでに生成済み
        $response = $this->get("/api/images/reviews/thumbnail/{$reviewImage->filename}");
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');

        // originalも取得できる
        $response = $this->get("/api/images/reviews/original/{$reviewImage->filename}");
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');

        // smallは初回アクセス時に生成される
        $response = $this->get("/api/images/reviews/small/{$reviewImage->filename}");
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');

        // 生成フラグが更新されているか確認
        $reviewImage->refresh();
        $this->assertTrue($reviewImage->isSizeGenerated('small'));
    }

    #[Test]
    public function it_returns_404_for_non_existent_images()
    {
        $response = $this->get('/api/images/reviews/thumbnail/nonexistent.jpg');
        $response->assertNotFound();
    }

    #[Test]
    public function it_validates_image_size_parameter()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // 無効なサイズパラメータ（ルートパターンに合わないため404）
        $response = $this->get("/api/images/reviews/invalid/{$reviewImage->filename}");
        $response->assertNotFound();
    }

    #[Test]
    public function it_respects_moderation_status()
    {
        // テスト画像を作成
        $testImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        // ReviewImageを作成
        $reviewImage = ReviewImage::createFromUpload($this->review->id, $testImage);

        // 画像を拒否状態に設定
        $reviewImage->update(['moderation_status' => 'rejected']);

        // アクセスが拒否されるか確認
        $response = $this->get("/api/images/reviews/thumbnail/{$reviewImage->filename}");
        $response->assertStatus(403);
    }
}
