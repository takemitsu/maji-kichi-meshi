<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShopImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate');

        // Seed categories
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);

        // ImageManagerをDIコンテナに登録（テスト環境用）
        $this->app->singleton(ImageManager::class, function () {
            return new ImageManager(new Driver);
        });
    }

    public function test_test_authenticated_user_can_upload_shop_images()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $images = [
            UploadedFile::fake()->image('image1.jpg', 800, 600),
            UploadedFile::fake()->image('image2.jpg', 800, 600),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'uploaded_count',
                    'images' => [
                        '*' => [
                            'id',
                            'urls',
                            'sort_order',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('shop_images', 2);
        $this->assertDatabaseHas('shop_images', [
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
        ]);
    }

    public function test_test_unauthenticated_user_cannot_upload_shop_images()
    {
        $shop = Shop::factory()->create();
        $image = UploadedFile::fake()->image('image.jpg');

        $response = $this->postJson("/api/shops/{$shop->id}/images", [
            'images' => [$image],
        ]);

        $response->assertStatus(401);
    }

    public function test_test_shop_image_upload_validates_file_types()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images", [
            'images' => [$invalidFile],
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'images.0',
            ],
        ]);
    }

    public function test_test_shop_image_upload_validates_file_size()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $largeFile = UploadedFile::fake()->image('image.jpg', 100, 100)->size(12000); // 12MB

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images", [
            'images' => [$largeFile],
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => ['images.0'],
        ]);
    }

    public function test_test_shop_image_upload_limits_maximum_images()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create 8 existing images
        ShopImage::factory()->count(8)->create(['shop_id' => $shop->id]);

        $images = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.jpg'),
            UploadedFile::fake()->image('image3.jpg'),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images", [
            'images' => $images,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Maximum 10 images allowed']);
    }

    public function test_test_authenticated_user_can_delete_shop_image()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $shopImage = ShopImage::factory()->create(['shop_id' => $shop->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/shops/{$shop->id}/images/{$shopImage->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Image deleted successfully']);

        $this->assertDatabaseMissing('shop_images', ['id' => $shopImage->id]);
    }

    public function test_test_user_cannot_delete_image_from_different_shop()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shopImage = ShopImage::factory()->create(['shop_id' => $shop2->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/shops/{$shop1->id}/images/{$shopImage->id}");

        $response->assertStatus(403)
            ->assertJsonFragment(['error' => 'Image does not belong to this shop']);
    }

    public function test_test_authenticated_user_can_reorder_shop_images()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $image1 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 0]);
        $image2 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 1]);
        $image3 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 2]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}/images/reorder", [
            'image_ids' => [$image3->id, $image1->id, $image2->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Images reordered successfully']);

        $this->assertDatabaseHas('shop_images', ['id' => $image3->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('shop_images', ['id' => $image1->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('shop_images', ['id' => $image2->id, 'sort_order' => 2]);
    }

    public function test_test_shop_images_are_included_in_shop_api_response()
    {
        Storage::fake('public');

        $shop = Shop::factory()->create();
        $image1 = ShopImage::factory()->published()->create(['shop_id' => $shop->id, 'sort_order' => 0]);
        $image2 = ShopImage::factory()->published()->create(['shop_id' => $shop->id, 'sort_order' => 1]);

        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'images' => [
                        '*' => [
                            'id',
                            'urls',
                            'sort_order',
                        ],
                    ],
                ],
            ]);

        $images = $response->json('data.images');
        $this->assertCount(2, $images);
        $this->assertEquals($image1->id, $images[0]['id']);
        $this->assertEquals($image2->id, $images[1]['id']);
    }
}
