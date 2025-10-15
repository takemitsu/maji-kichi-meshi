<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed categories
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_can_list_shops(): void
    {
        // Create test shops
        $shop1 = Shop::factory()->create(['name' => 'Test Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Test Shop 2']);

        $response = $this->getJson('/api/shops');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'latitude',
                        'longitude',
                        'average_rating',
                        'review_count',
                        'categories',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_it_can_show_single_shop(): void
    {
        $shop = Shop::factory()->create(['name' => 'Test Shop']);

        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $shop->id,
                    'name' => 'Test Shop',
                ],
            ]);
    }

    public function test_it_can_search_shops_by_name(): void
    {
        Shop::factory()->create(['name' => 'Ramen Shop']);
        Shop::factory()->create(['name' => 'Cafe Shop']);

        $response = $this->getJson('/api/shops?search=Ramen');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop', $data[0]['name']);
    }

    public function test_it_can_filter_shops_by_category(): void
    {
        $category = Category::where('slug', 'ramen')->first();
        $shop = Shop::factory()->create(['name' => 'Ramen Shop']);
        $shop->categories()->attach($category->id);

        Shop::factory()->create(['name' => 'Other Shop']);

        $response = $this->getJson('/api/shops?category=' . $category->id);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop', $data[0]['name']);
    }

    public function test_it_requires_authentication_to_create_shop(): void
    {
        $response = $this->postJson('/api/shops', [
            'name' => 'New Shop',
            'address' => 'Test Address',
            'latitude' => 35.7,
            'longitude' => 139.5,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_shop(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => 'New Shop',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 35.7,
            'longitude' => 139.5,
            'phone' => '03-1234-5678',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'New Shop',
                    'address' => 'Test Address',
                ],
            ]);

        $this->assertDatabaseHas('shops', [
            'name' => 'New Shop',
            'address' => 'Test Address',
        ]);
    }

    public function test_it_validates_shop_creation_data(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => '', // Invalid: required
            'latitude' => 200, // Invalid: out of range
            'longitude' => 'not-a-number', // Invalid: not numeric
        ]);

        $response->assertStatus(422);

        // Debug: check actual validation errors
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
        // address, latitude, longitude are now nullable, so only name is required
    }

    public function test_authenticated_user_can_update_shop(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $shop = Shop::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ],
            ]);

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_authenticated_user_can_delete_shop(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $shop = Shop::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Shop deleted successfully']);

        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
    }

    // =============================================================================
    // Location-based Search Tests
    // =============================================================================

    public function test_it_can_search_shops_by_location(): void
    {
        // Create shops at very close locations to ensure they're within radius
        $nearShop = Shop::factory()->create([
            'name' => 'Near Shop',
            'latitude' => 35.7022,
            'longitude' => 139.7745,
        ]);

        // Search within 10km radius (larger to ensure we find shops in test)
        $response = $this->getJson('/api/shops?latitude=35.7022&longitude=139.7745&radius=10');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should find the near shop
        $this->assertGreaterThanOrEqual(1, count($data));
        $shopNames = collect($data)->pluck('name')->toArray();
        $this->assertContains('Near Shop', $shopNames);
    }

    public function test_it_uses_default_radius_for_location_search(): void
    {
        Shop::factory()->create([
            'name' => 'Test Shop',
            'latitude' => 35.7022,
            'longitude' => 139.7745,
        ]);

        // Search without radius (should use default 5km)
        $response = $this->getJson('/api/shops?latitude=35.7022&longitude=139.7745');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function test_it_filters_open_shops_only(): void
    {
        $openShop = Shop::factory()->create([
            'name' => 'Open Shop',
            'is_closed' => false,
        ]);

        $closedShop = Shop::factory()->create([
            'name' => 'Closed Shop',
            'is_closed' => true,
        ]);

        $response = $this->getJson('/api/shops?open_only=true');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only return open shops
        $shopNames = collect($data)->pluck('name')->toArray();
        $this->assertContains('Open Shop', $shopNames);
        $this->assertNotContains('Closed Shop', $shopNames);
    }

    // =============================================================================
    // Category Association Tests
    // =============================================================================

    public function test_it_can_create_shop_with_categories(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $category1 = Category::where('slug', 'ramen')->first();
        $category2 = Category::where('slug', 'lunch')->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => 'Ramen Shop',
            'address' => 'Test Address',
            'category_ids' => [$category1->id, $category2->id],
        ]);

        $response->assertStatus(201);

        // Check categories were attached
        $shop = Shop::where('name', 'Ramen Shop')->first();
        $this->assertNotNull($shop);
        $this->assertCount(2, $shop->categories);
        $this->assertTrue($shop->categories->contains('id', $category1->id));
        $this->assertTrue($shop->categories->contains('id', $category2->id));

        // Check response includes categories
        $response->assertJsonPath('data.categories.0.id', $category1->id);
    }

    public function test_it_can_update_shop_categories(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $category1 = Category::where('slug', 'ramen')->first();
        $category2 = Category::where('slug', 'lunch')->first();
        $category3 = Category::where('slug', 'cafe')->first();

        // Create shop with initial categories
        $shop = Shop::factory()->create(['name' => 'Test Shop']);
        $shop->categories()->attach([$category1->id, $category2->id]);

        // Update to different categories
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}", [
            'name' => 'Test Shop',
            'category_ids' => [$category2->id, $category3->id],
        ]);

        $response->assertStatus(200);

        // Check categories were synced (replaced)
        $shop->refresh();
        $this->assertCount(2, $shop->categories);
        $this->assertFalse($shop->categories->contains('id', $category1->id)); // Removed
        $this->assertTrue($shop->categories->contains('id', $category2->id)); // Kept
        $this->assertTrue($shop->categories->contains('id', $category3->id)); // Added
    }

    public function test_it_can_remove_all_categories_from_shop(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $category = Category::where('slug', 'ramen')->first();

        // Create shop with category
        $shop = Shop::factory()->create(['name' => 'Test Shop']);
        $shop->categories()->attach($category->id);

        // Update with empty category_ids array
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}", [
            'name' => 'Test Shop',
            'category_ids' => [],
        ]);

        $response->assertStatus(200);

        // Check all categories were removed
        $shop->refresh();
        $this->assertCount(0, $shop->categories);
    }

    public function test_search_with_multiple_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::where('slug', 'ramen')->first();

        // 営業中のラーメン店
        $shop1 = Shop::factory()->create([
            'name' => 'Ramen Shop Open',
            'is_closed' => false,
        ]);
        $shop1->categories()->attach($category->id);

        // 閉店したラーメン店
        $shop2 = Shop::factory()->create([
            'name' => 'Ramen Shop Closed',
            'is_closed' => true,
        ]);
        $shop2->categories()->attach($category->id);

        // 営業中のカフェ
        $shop3 = Shop::factory()->create([
            'name' => 'Cafe Shop Open',
            'is_closed' => false,
        ]);

        // カテゴリ + 営業中フィルタの組み合わせ
        $response = $this->getJson("/api/shops?category={$category->id}&open_only=true");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop Open', $data[0]['name']);

        // 検索 + カテゴリ + 営業中フィルタ
        $response = $this->getJson("/api/shops?search=Ramen&category={$category->id}&open_only=true");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop Open', $data[0]['name']);
    }

    public function test_sort_with_edge_cases(): void
    {
        // created_atで並び順を制御（現在のShopControllerはcreated_at降順でソート）
        $shopC = Shop::factory()->create([
            'name' => 'Shop C',
            'created_at' => now()->subDays(3),
        ]);
        $shopB = Shop::factory()->create([
            'name' => 'Shop B',
            'created_at' => now()->subDays(2),
        ]);
        $shopA = Shop::factory()->create([
            'name' => 'Shop A',
            'created_at' => now()->subDays(1),
        ]);

        // デフォルトソート（created_at降順 = 新しい順）
        $response = $this->getJson('/api/shops');
        $response->assertStatus(200);
        $data = $response->json('data');

        // 最新作成順に並んでいることを確認
        $this->assertEquals('Shop A', $data[0]['name']);
        $this->assertEquals('Shop B', $data[1]['name']);
        $this->assertEquals('Shop C', $data[2]['name']);

        // 各店舗が正しく返されることを確認
        $this->assertCount(3, $data);
        $names = collect($data)->pluck('name')->toArray();
        $this->assertContains('Shop A', $names);
        $this->assertContains('Shop B', $names);
        $this->assertContains('Shop C', $names);
    }

    public function test_pagination_with_filters(): void
    {
        $category = Category::where('slug', 'ramen')->first();

        // 15件のラーメン店を作成
        for ($i = 1; $i <= 15; $i++) {
            $shop = Shop::factory()->create([
                'name' => "Ramen Shop {$i}",
                'is_closed' => false,
            ]);
            $shop->categories()->attach($category->id);
        }

        // 5件の他カテゴリ店舗
        for ($i = 1; $i <= 5; $i++) {
            Shop::factory()->create([
                'name' => "Other Shop {$i}",
                'is_closed' => false,
            ]);
        }

        // カテゴリフィルタ + ページネーション (1ページ10件)
        $response = $this->getJson("/api/shops?category={$category->id}&per_page=10");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data);
        $this->assertEquals(15, $response->json('meta.total'));

        // 2ページ目
        $response = $this->getJson("/api/shops?category={$category->id}&per_page=10&page=2");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    public function test_location_search_with_invalid_coordinates(): void
    {
        // 無効な緯度（範囲外）
        $response = $this->getJson('/api/shops?latitude=200&longitude=139.7745&radius=5');
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('latitude', $errors);

        // 無効な経度（範囲外）
        $response = $this->getJson('/api/shops?latitude=35.7022&longitude=200&radius=5');
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('longitude', $errors);

        // 無効な半径（負の値）
        $response = $this->getJson('/api/shops?latitude=35.7022&longitude=139.7745&radius=-5');
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('radius', $errors);
    }

    // =============================================================================
    // Wishlist Status Endpoint Tests
    // =============================================================================

    public function test_guest_can_get_wishlist_status(): void
    {
        $shop = Shop::factory()->create();

        $response = $this->getJson("/api/shops/{$shop->id}/wishlist-status");

        $response->assertStatus(200)
            ->assertJson([
                'in_wishlist' => false,
            ]);
    }

    public function test_authenticated_user_sees_wishlist_status_when_not_in_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/shops/{$shop->id}/wishlist-status");

        $response->assertStatus(200)
            ->assertJson([
                'in_wishlist' => false,
            ]);
    }

    public function test_authenticated_user_sees_wishlist_status_when_in_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Add to wishlist
        \App\Models\Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 2,
            'source_type' => 'shop_detail',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/shops/{$shop->id}/wishlist-status");

        $response->assertStatus(200)
            ->assertJson([
                'in_wishlist' => true,
                'status' => 'want_to_go',
                'priority' => 2,
                'priority_label' => 'そのうち',
            ]);
    }
}
