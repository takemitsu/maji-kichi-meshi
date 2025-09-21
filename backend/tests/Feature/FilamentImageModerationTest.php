<?php

namespace Tests\Feature;

use App\Filament\Resources\ReviewImageResource;
use App\Filament\Resources\ShopImageResource;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentImageModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // ImageManagerをDIコンテナに登録（テスト環境用）
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver);
        });

        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);

        $this->actingAs($this->admin, 'web');
    }

    public function test_review_image_approve_action_calls_model_method()
    {
        $review = Review::factory()->create();
        $reviewImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'under_review',
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        Livewire::test(ReviewImageResource\Pages\ListReviewImages::class)
            ->callTableAction('approve', $reviewImage);

        $reviewImage->refresh();

        $this->assertEquals('published', $reviewImage->moderation_status);
        $this->assertEquals($this->admin->id, $reviewImage->moderated_by);
        $this->assertNotNull($reviewImage->moderated_at);
    }

    public function test_review_image_reject_action_calls_model_method()
    {
        $review = Review::factory()->create();
        $reviewImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'under_review',
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        Livewire::test(ReviewImageResource\Pages\ListReviewImages::class)
            ->callTableAction('reject', $reviewImage);

        $reviewImage->refresh();

        $this->assertEquals('rejected', $reviewImage->moderation_status);
        $this->assertEquals($this->admin->id, $reviewImage->moderated_by);
        $this->assertNotNull($reviewImage->moderated_at);
    }

    public function test_review_image_bulk_approve()
    {
        $review = Review::factory()->create();
        $images = ReviewImage::factory()->count(3)->for($review)->create([
            'moderation_status' => 'under_review',
        ]);

        Livewire::test(ReviewImageResource\Pages\ListReviewImages::class)
            ->callTableBulkAction('approve', $images);

        foreach ($images as $image) {
            $image->refresh();
            $this->assertEquals('published', $image->moderation_status);
            $this->assertEquals($this->admin->id, $image->moderated_by);
        }
    }

    public function test_review_image_bulk_reject()
    {
        $review = Review::factory()->create();
        $images = ReviewImage::factory()->count(3)->for($review)->create([
            'moderation_status' => 'under_review',
        ]);

        Livewire::test(ReviewImageResource\Pages\ListReviewImages::class)
            ->callTableBulkAction('reject', $images);

        foreach ($images as $image) {
            $image->refresh();
            $this->assertEquals('rejected', $image->moderation_status);
            $this->assertEquals($this->admin->id, $image->moderated_by);
        }
    }

    public function test_shop_image_approve_action_calls_model_method()
    {
        $shop = Shop::factory()->create();
        $shopImage = ShopImage::factory()->for($shop)->create([
            'moderation_status' => 'under_review',
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        Livewire::test(ShopImageResource\Pages\ListShopImages::class)
            ->callTableAction('approve', $shopImage);

        $shopImage->refresh();

        $this->assertEquals('published', $shopImage->moderation_status);
        $this->assertEquals($this->admin->id, $shopImage->moderated_by);
        $this->assertNotNull($shopImage->moderated_at);
    }

    public function test_shop_image_reject_action_calls_model_method()
    {
        $shop = Shop::factory()->create();
        $shopImage = ShopImage::factory()->for($shop)->create([
            'moderation_status' => 'under_review',
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        Livewire::test(ShopImageResource\Pages\ListShopImages::class)
            ->callTableAction('reject', $shopImage);

        $shopImage->refresh();

        $this->assertEquals('rejected', $shopImage->moderation_status);
        $this->assertEquals($this->admin->id, $shopImage->moderated_by);
        $this->assertNotNull($shopImage->moderated_at);
    }

    public function test_shop_image_defaults_to_published_on_creation()
    {
        $shop = Shop::factory()->create();

        $testFile = UploadedFile::fake()->image('shop.jpg', 800, 600);

        $shopImage = ShopImage::createFromUpload($shop->id, $testFile);

        $this->assertEquals('published', $shopImage->moderation_status);
    }

    public function test_review_image_view_action_uses_urls_array()
    {
        $review = Review::factory()->create();
        $reviewImage = ReviewImage::factory()->for($review)->create([
            'filename' => 'test-image.jpg',
        ]);

        $page = Livewire::test(ReviewImageResource\Pages\ListReviewImages::class);

        $expectedUrl = $reviewImage->urls['medium'];

        $page->assertTableActionHasUrl('view_image', $expectedUrl, record: $reviewImage);
    }

    public function test_approve_action_only_visible_when_not_published()
    {
        $review = Review::factory()->create();

        $publishedImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'published',
        ]);

        $underReviewImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'under_review',
        ]);

        $page = Livewire::test(ReviewImageResource\Pages\ListReviewImages::class);

        $page->assertTableActionHidden('approve', record: $publishedImage);
        $page->assertTableActionVisible('approve', record: $underReviewImage);
    }

    public function test_reject_action_only_visible_when_not_rejected()
    {
        $review = Review::factory()->create();

        $rejectedImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'rejected',
        ]);

        $underReviewImage = ReviewImage::factory()->for($review)->create([
            'moderation_status' => 'under_review',
        ]);

        $page = Livewire::test(ReviewImageResource\Pages\ListReviewImages::class);

        $page->assertTableActionHidden('reject', record: $rejectedImage);
        $page->assertTableActionVisible('reject', record: $underReviewImage);
    }
}
