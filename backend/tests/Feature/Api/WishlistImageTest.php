<?php

namespace Tests\Feature\Api;

use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class WishlistImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_wishlist_returns_shop_images_in_correct_format(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create shop image
        $shopImage = ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
        ]);

        // Add shop to wishlist
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'priority' => 2,
            'status' => 'want_to_go',
            'source_type' => 'shop_detail',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-wishlist?status=want_to_go');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertArrayHasKey('shop', $data[0]);
        $this->assertArrayHasKey('images', $data[0]['shop']);
        $this->assertCount(1, $data[0]['shop']['images']);

        // Verify image structure
        $image = $data[0]['shop']['images'][0];
        $this->assertArrayHasKey('id', $image);
        $this->assertArrayHasKey('urls', $image);
        $this->assertArrayHasKey('sort_order', $image);

        // Verify urls structure
        $this->assertArrayHasKey('original', $image['urls']);
        $this->assertArrayHasKey('large', $image['urls']);
        $this->assertArrayHasKey('medium', $image['urls']);
        $this->assertArrayHasKey('thumbnail', $image['urls']);
    }

    public function test_wishlist_does_not_return_unpublished_images(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create published and unpublished images
        ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
        ]);
        ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'rejected',
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'source_type' => 'shop_detail',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-wishlist?status=want_to_go');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only return the published image
        $this->assertCount(1, $data[0]['shop']['images']);
    }

    public function test_wishlist_returns_empty_images_array_when_no_images(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'source_type' => 'shop_detail',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-wishlist?status=want_to_go');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertArrayHasKey('shop', $data[0]);
        $this->assertArrayHasKey('images', $data[0]['shop']);
        $this->assertCount(0, $data[0]['shop']['images']);
    }

    public function test_wishlist_images_are_sorted_by_sort_order(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create images with specific sort order
        $image1 = ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
            'sort_order' => 2,
        ]);
        $image2 = ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
            'sort_order' => 1,
        ]);
        $image3 = ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
            'sort_order' => 3,
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'source_type' => 'shop_detail',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-wishlist?status=want_to_go');

        $response->assertStatus(200);
        $data = $response->json('data');

        $images = $data[0]['shop']['images'];
        $this->assertCount(3, $images);

        // Should be sorted by sort_order ASC
        $this->assertEquals($image2->id, $images[0]['id']); // sort_order 1
        $this->assertEquals($image1->id, $images[1]['id']); // sort_order 2
        $this->assertEquals($image3->id, $images[2]['id']); // sort_order 3
    }
}
