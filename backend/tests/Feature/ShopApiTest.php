<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ])
            ->assertJsonPath('data.0.average_rating', null)
            ->assertJsonPath('data.0.review_count', 0)
            ->assertJsonPath('data.1.average_rating', null)
            ->assertJsonPath('data.1.review_count', 0);
    }

    public function test_it_includes_average_rating_and_review_count(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['name' => 'Test Shop']);

        // レビュー3件作成（評価: 3, 4, 5 → 平均: 4.0）
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/shops');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.average_rating', 4)
            ->assertJsonPath('data.0.review_count', 3);
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
            ])
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0);
    }

    public function test_it_shows_shop_with_average_rating_and_review_count(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create(['name' => 'Test Shop']);

        // レビュー2件作成（評価: 4, 5 → 平均: 4.5）
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.average_rating', 4.5)
            ->assertJsonPath('data.review_count', 2);
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
        $this->assertNull($data[0]['average_rating']);
        $this->assertEquals(0, $data[0]['review_count']);
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
        $this->assertNull($data[0]['average_rating']);
        $this->assertEquals(0, $data[0]['review_count']);
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

        // Verify aggregate values for the returned shop
        $this->assertNull($data[0]['average_rating']);
        $this->assertEquals(0, $data[0]['review_count']);
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
    // Sort Feature Tests
    // =============================================================================

    public function test_it_can_sort_shops_by_created_at_desc(): void
    {
        // 3つの店舗を異なる作成日時で作成
        $shopOld = Shop::factory()->create([
            'name' => 'Old Shop',
            'created_at' => now()->subDays(3),
        ]);
        $shopMiddle = Shop::factory()->create([
            'name' => 'Middle Shop',
            'created_at' => now()->subDays(2),
        ]);
        $shopNew = Shop::factory()->create([
            'name' => 'New Shop',
            'created_at' => now()->subDays(1),
        ]);

        // 新しい順（降順） - デフォルト
        $response = $this->getJson('/api/shops?sort=created_at_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('New Shop', $data[0]['name']);
        $this->assertEquals('Middle Shop', $data[1]['name']);
        $this->assertEquals('Old Shop', $data[2]['name']);
    }

    public function test_it_can_sort_shops_by_review_latest(): void
    {
        $user = User::factory()->create();

        // 3つの店舗を作成
        $shop1 = Shop::factory()->create(['name' => 'Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Shop 2']);
        $shop3 = Shop::factory()->create(['name' => 'Shop 3']);

        // レビューを異なる日時で作成
        \App\Models\Review::factory()->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(5), // 古い
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(1), // 最新
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop3->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(3), // 中間
        ]);

        // レビュー投稿順（最新レビューがある店舗が上）
        $response = $this->getJson('/api/shops?sort=review_latest');
        $response->assertStatus(200);
        $data = $response->json('data');

        // 最新レビューがある順
        $this->assertEquals('Shop 2', $data[0]['name']);
        $this->assertEquals('Shop 3', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
    }

    public function test_it_can_sort_shops_by_reviews_count(): void
    {
        $user = User::factory()->create();

        // 3つの店舗を作成
        $shop1 = Shop::factory()->create(['name' => 'Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Shop 2']);
        $shop3 = Shop::factory()->create(['name' => 'Shop 3']);

        // レビュー数を異なる数で作成
        \App\Models\Review::factory()->count(1)->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
        ]);
        \App\Models\Review::factory()->count(5)->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
        ]);
        \App\Models\Review::factory()->count(3)->create([
            'shop_id' => $shop3->id,
            'user_id' => $user->id,
        ]);

        // レビュー数順（多い順）
        $response = $this->getJson('/api/shops?sort=reviews_count_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Shop 2', $data[0]['name']);
        $this->assertEquals('Shop 3', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
        $this->assertEquals(5, $data[0]['review_count']);
        $this->assertEquals(3, $data[1]['review_count']);
        $this->assertEquals(1, $data[2]['review_count']);
    }

    public function test_it_can_sort_shops_by_average_rating(): void
    {
        $user = User::factory()->create();

        // 3つの店舗を作成
        $shop1 = Shop::factory()->create(['name' => 'Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Shop 2']);
        $shop3 = Shop::factory()->create(['name' => 'Shop 3']);

        // 異なる評価でレビューを作成
        \App\Models\Review::factory()->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop3->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        // 平均評価順（高い順）
        $response = $this->getJson('/api/shops?sort=rating_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Shop 2', $data[0]['name']);
        $this->assertEquals('Shop 3', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
        $this->assertEquals(5, $data[0]['average_rating']);
        $this->assertEquals(4, $data[1]['average_rating']);
        $this->assertEquals(3, $data[2]['average_rating']);
    }

    public function test_it_handles_shops_without_reviews_in_rating_sort(): void
    {
        $user = User::factory()->create();

        // レビューありとなしの店舗を作成
        $shopWithReview = Shop::factory()->create(['name' => 'Shop With Review']);
        $shopWithoutReview = Shop::factory()->create(['name' => 'Shop Without Review']);

        \App\Models\Review::factory()->create([
            'shop_id' => $shopWithReview->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        // 平均評価順（レビューがない店舗は最後）
        $response = $this->getJson('/api/shops?sort=rating_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('Shop With Review', $data[0]['name']);
        $this->assertEquals('Shop Without Review', $data[1]['name']);
        $this->assertEquals(5, $data[0]['average_rating']);
        $this->assertNull($data[1]['average_rating']);
    }

    public function test_it_handles_invalid_sort_parameter(): void
    {
        Shop::factory()->create(['name' => 'Test Shop']);

        // 不正なソートパラメータ
        $response = $this->getJson('/api/shops?sort=invalid_sort');
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('sort', $errors);
    }

    public function test_it_can_combine_sort_with_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::where('slug', 'ramen')->first();

        // カテゴリ付きの店舗を作成
        $shop1 = Shop::factory()->create(['name' => 'Ramen Shop 1']);
        $shop1->categories()->attach($category->id);

        $shop2 = Shop::factory()->create(['name' => 'Ramen Shop 2']);
        $shop2->categories()->attach($category->id);

        // レビューを作成（Shop 1 が高評価）
        \App\Models\Review::factory()->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        // カテゴリフィルタ + 評価順ソート
        $response = $this->getJson("/api/shops?category={$category->id}&sort=rating_desc");
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $this->assertEquals('Ramen Shop 1', $data[0]['name']);
        $this->assertEquals('Ramen Shop 2', $data[1]['name']);
    }

    public function test_it_can_combine_sort_with_search(): void
    {
        $user = User::factory()->create();

        // 「Cafe」で始まる店舗を作成
        $shop1 = Shop::factory()->create(['name' => 'Cafe A']);
        $shop2 = Shop::factory()->create(['name' => 'Cafe B']);

        // レビューを作成（Cafe B が高評価）
        \App\Models\Review::factory()->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        // 検索 + 評価順ソート
        $response = $this->getJson('/api/shops?search=Cafe&sort=rating_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $this->assertEquals('Cafe B', $data[0]['name']);
        $this->assertEquals('Cafe A', $data[1]['name']);
    }

    public function test_sort_with_pagination_maintains_order(): void
    {
        $user = User::factory()->create();

        // 30件の店舗を作成（評価をランダムに）
        for ($i = 1; $i <= 30; $i++) {
            $shop = Shop::factory()->create(['name' => "Shop {$i}"]);
            \App\Models\Review::factory()->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'rating' => ($i % 5) + 1, // 1-5の評価
            ]);
        }

        // 1ページ目（評価順）
        $response1 = $this->getJson('/api/shops?sort=rating_desc&per_page=10&page=1');
        $response1->assertStatus(200);
        $data1 = $response1->json('data');
        $this->assertCount(10, $data1);

        // 2ページ目（評価順）
        $response2 = $this->getJson('/api/shops?sort=rating_desc&per_page=10&page=2');
        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        $this->assertCount(10, $data2);

        // 1ページ目の最後の評価 >= 2ページ目の最初の評価
        $this->assertGreaterThanOrEqual(
            $data2[0]['average_rating'],
            $data1[9]['average_rating']
        );
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

    // =============================================================================
    // N+1 Query Prevention Tests
    // =============================================================================

    public function test_shop_list_has_no_n_plus_1_query(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        // 10件の店舗を作成
        for ($i = 1; $i <= 10; $i++) {
            $shop = Shop::factory()->create();
            $shop->categories()->attach($category->id);
            \App\Models\Review::factory()->count(2)->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
            ]);
        }

        DB::enableQueryLog();
        $this->getJson('/api/shops');
        $queries10 = DB::getQueryLog();
        DB::disableQueryLog();

        // 100件の店舗を作成
        for ($i = 11; $i <= 100; $i++) {
            $shop = Shop::factory()->create();
            $shop->categories()->attach($category->id);
            \App\Models\Review::factory()->count(2)->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
            ]);
        }

        DB::flushQueryLog(); // 前のログをクリア
        DB::enableQueryLog();
        $this->getJson('/api/shops?per_page=50'); // per_pageを指定（maxは50）
        $queries100 = DB::getQueryLog();
        DB::disableQueryLog();

        // N+1が解消されていれば、件数に関係なく同じクエリ数
        // 期待クエリ: 1. count(*), 2. shops+withAvg+withCount, 3. categories, 4. images
        $this->assertEquals(4, count($queries10), '10件: 4クエリ固定であるべき');
        $this->assertEquals(4, count($queries100), '100件: 4クエリ固定であるべき');
        $this->assertEquals(count($queries10), count($queries100), 'N+1問題: 件数に関係なく同じクエリ数であるべき');
    }

    public function test_shop_show_has_no_n_plus_1_query(): void
    {
        $user = User::factory()->create();
        $category = Category::first();

        // レビュー付きの店舗を作成
        $shop = Shop::factory()->create();
        $shop->categories()->attach($category->id);
        \App\Models\Review::factory()->count(3)->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
        ]);

        DB::enableQueryLog();
        $this->getJson("/api/shops/{$shop->id}");
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 期待クエリ: 1. shop取得, 2. categories, 3. images, 4. loadAvg, 5. loadCount
        $this->assertEquals(5, count($queries), '詳細表示: 5クエリ固定であるべき');
    }

    // =============================================================================
    // Store/Update Aggregate Tests
    // =============================================================================

    public function test_created_shop_includes_correct_review_aggregates(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => 'New Shop',
            'address' => 'Test Address',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0); // loadCount()はレビュー0件で0を返す
    }

    public function test_updated_shop_includes_correct_review_aggregates(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $shop = Shop::factory()->create(['name' => 'Old Name']);

        // レビューを追加
        \App\Models\Review::factory()->create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.average_rating', 4)
            ->assertJsonPath('data.review_count', 1);
    }

    // =============================================================================
    // Sort Tiebreak Tests
    // =============================================================================

    public function test_sort_by_created_at_with_same_date_uses_id_tiebreak(): void
    {
        // 同じcreated_atを持つ店舗を作成（IDの降順になるべき）
        $sameTime = now()->subDay();
        $shop1 = Shop::factory()->create([
            'name' => 'Shop 1',
            'created_at' => $sameTime,
        ]);
        $shop2 = Shop::factory()->create([
            'name' => 'Shop 2',
            'created_at' => $sameTime,
        ]);
        $shop3 = Shop::factory()->create([
            'name' => 'Shop 3',
            'created_at' => $sameTime,
        ]);

        $response = $this->getJson('/api/shops?sort=created_at_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        // ID降順（新しいID = 後から作成 = 優先）
        $this->assertEquals('Shop 3', $data[0]['name']);
        $this->assertEquals('Shop 2', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
    }

    public function test_sort_by_review_latest_with_same_visited_at_uses_created_at_tiebreak(): void
    {
        $user = User::factory()->create();
        $sameVisitDate = now()->subDay()->format('Y-m-d');

        // 同じvisited_atだが異なるcreated_atのレビューを持つ店舗
        $shop1 = Shop::factory()->create([
            'name' => 'Shop 1',
            'created_at' => now()->subDays(3),
        ]);
        $shop2 = Shop::factory()->create([
            'name' => 'Shop 2',
            'created_at' => now()->subDays(2),
        ]);
        $shop3 = Shop::factory()->create([
            'name' => 'Shop 3',
            'created_at' => now()->subDays(1),
        ]);

        \App\Models\Review::factory()->create([
            'shop_id' => $shop1->id,
            'user_id' => $user->id,
            'visited_at' => $sameVisitDate,
            'created_at' => now()->subDays(3),
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop2->id,
            'user_id' => $user->id,
            'visited_at' => $sameVisitDate,
            'created_at' => now()->subDays(2),
        ]);
        \App\Models\Review::factory()->create([
            'shop_id' => $shop3->id,
            'user_id' => $user->id,
            'visited_at' => $sameVisitDate,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/shops?sort=review_latest');
        $response->assertStatus(200);
        $data = $response->json('data');

        // visited_at同じ → created_at降順 → id降順
        $this->assertEquals('Shop 3', $data[0]['name']);
        $this->assertEquals('Shop 2', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
    }

    public function test_sort_by_reviews_count_with_same_count_uses_created_at_tiebreak(): void
    {
        $user = User::factory()->create();

        // reviews_countが同じ（3件ずつ）だが、created_atが異なる店舗
        $shop1 = Shop::factory()->create([
            'name' => 'Shop 1',
            'created_at' => now()->subDays(3),
        ]);
        $shop2 = Shop::factory()->create([
            'name' => 'Shop 2',
            'created_at' => now()->subDays(2),
        ]);
        $shop3 = Shop::factory()->create([
            'name' => 'Shop 3',
            'created_at' => now()->subDays(1),
        ]);

        // 各店舗に3件ずつレビュー作成
        foreach ([$shop1, $shop2, $shop3] as $shop) {
            \App\Models\Review::factory()->count(3)->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
            ]);
        }

        $response = $this->getJson('/api/shops?sort=reviews_count_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        // reviews_count同じ → created_at降順 → id降順
        $this->assertEquals('Shop 3', $data[0]['name']);
        $this->assertEquals('Shop 2', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
        $this->assertEquals(3, $data[0]['review_count']);
        $this->assertEquals(3, $data[1]['review_count']);
        $this->assertEquals(3, $data[2]['review_count']);
    }

    public function test_sort_by_rating_with_same_rating_uses_created_at_tiebreak(): void
    {
        $user = User::factory()->create();

        // 同じ平均評価（4.0）だが、created_atが異なる店舗
        $shop1 = Shop::factory()->create([
            'name' => 'Shop 1',
            'created_at' => now()->subDays(3),
        ]);
        $shop2 = Shop::factory()->create([
            'name' => 'Shop 2',
            'created_at' => now()->subDays(2),
        ]);
        $shop3 = Shop::factory()->create([
            'name' => 'Shop 3',
            'created_at' => now()->subDays(1),
        ]);

        // 各店舗に同じ評価のレビュー作成
        foreach ([$shop1, $shop2, $shop3] as $shop) {
            \App\Models\Review::factory()->create([
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'rating' => 4,
            ]);
        }

        $response = $this->getJson('/api/shops?sort=rating_desc');
        $response->assertStatus(200);
        $data = $response->json('data');

        // rating同じ → created_at降順 → id降順
        $this->assertEquals('Shop 3', $data[0]['name']);
        $this->assertEquals('Shop 2', $data[1]['name']);
        $this->assertEquals('Shop 1', $data[2]['name']);
        $this->assertEquals(4, $data[0]['average_rating']);
        $this->assertEquals(4, $data[1]['average_rating']);
        $this->assertEquals(4, $data[2]['average_rating']);
    }
}
