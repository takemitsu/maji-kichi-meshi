<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shop;
use App\Models\ShopImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_authenticated_user_can_upload_shop_images()
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
                             ]
                         ]
                     ]
                 ]);
        
        $this->assertDatabaseCount('shop_images', 2);
        $this->assertDatabaseHas('shop_images', [
            'shop_id' => $shop->id,
            'status' => 'published',
        ]);
    }
    
    public function test_unauthenticated_user_cannot_upload_shop_images()
    {
        $shop = Shop::factory()->create();
        $image = UploadedFile::fake()->image('image.jpg');
        
        $response = $this->postJson("/api/shops/{$shop->id}/images", [
            'images' => [$image],
        ]);
        
        $response->assertStatus(401);
    }
    
    public function test_shop_image_upload_validates_file_types()
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
        $response->assertJson([
            'error' => 'Validation failed',
            'messages' => [
                'images.0' => [
                    'The images.0 field must be an image.',
                    'The images.0 field must be a file of type: jpeg, png, jpg, gif, webp.'
                ]
            ]
        ]);
    }
    
    public function test_shop_image_upload_validates_file_size()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $largeFile = UploadedFile::fake()->create('image.jpg', 11 * 1024); // 11MB
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images", [
            'images' => [$largeFile],
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('messages.images.0.0', 'The images.0 field must not be greater than 10240 kilobytes.');
    }
    
    public function test_shop_image_upload_limits_maximum_images()
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
                 ->assertJsonFragment(['error' => 'Maximum 10 images allowed per shop']);
    }
    
    public function test_authenticated_user_can_delete_shop_image()
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
    
    public function test_user_cannot_delete_image_from_different_shop()
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
    
    public function test_authenticated_user_can_reorder_shop_images()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $image1 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 0]);
        $image2 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 1]);
        $image3 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 2]);
        $token = JWTAuth::fromUser($user);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/shops/{$shop->id}/images/reorder", [
            'image_ids' => [$image3->id, $image1->id, $image2->id],
        ]);
        
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Images reordered successfully']);
        
        $this->assertDatabaseHas('shop_images', ['id' => $image3->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('shop_images', ['id' => $image1->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('shop_images', ['id' => $image2->id, 'sort_order' => 2]);
    }
    
    public function test_shop_images_are_included_in_shop_api_response()
    {
        Storage::fake('public');
        
        $shop = Shop::factory()->create();
        $image1 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 0]);
        $image2 = ShopImage::factory()->create(['shop_id' => $shop->id, 'sort_order' => 1]);
        
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
                             ]
                         ]
                     ]
                 ]);
        
        $images = $response->json('data.images');
        $this->assertCount(2, $images);
        $this->assertEquals($image1->id, $images[0]['id']);
        $this->assertEquals($image2->id, $images[1]['id']);
    }
}