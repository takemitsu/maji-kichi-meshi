<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * 店舗APIの認証統合テスト
 *
 * 店舗一覧・詳細での行きたいリストの表示を、
 * 認証状態別に検証する統合テスト
 */
class ShopApiAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    // =============================================================================
    // 店舗一覧での行きたい表示テスト
    // =============================================================================

    public function test_shop_list_shows_wishlist_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 行きたいリストに追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/shops');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // 行きたいステータスが返る
        $this->assertTrue($data['wishlist_status']['in_wishlist']);
        $this->assertEquals('want_to_go', $data['wishlist_status']['status']);
        $this->assertEquals(3, $data['wishlist_status']['priority']);
    }

    public function test_shop_list_hides_wishlist_for_guest(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 他のユーザーの行きたいを追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'shop_detail',
        ]);

        // 未ログインでアクセス
        $response = $this->getJson('/api/shops');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // 行きたい状態は false（他人の状態を見せない）
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // 店舗詳細での行きたい表示テスト
    // =============================================================================

    public function test_shop_detail_shows_wishlist_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 行きたいリストに追加（行った状態）
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'priority' => 2,
            'source_type' => 'shop_detail',
            'visited_at' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // 行った状態が返る
        $this->assertTrue($data['wishlist_status']['in_wishlist']);
        $this->assertEquals('visited', $data['wishlist_status']['status']);
    }

    public function test_shop_detail_hides_wishlist_for_guest(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 他のユーザーの行きたいを追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        // 未ログインでアクセス
        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // 行きたい状態は false
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // 複数ユーザーの行きたいがある場合
    // =============================================================================

    public function test_shop_list_shows_only_own_wishlist_status(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop1 = Shop::factory()->create(['name' => 'Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Shop 2']);

        // user1 が shop1 を行きたい
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop1->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        // user2 が shop1 を行った（訪問済み）
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop1->id,
            'status' => 'visited',
            'priority' => 2,
            'source_type' => 'shop_detail',
        ]);

        // user2 が shop2 を行きたい
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop2->id,
            'status' => 'want_to_go',
            'priority' => 1,
            'source_type' => 'shop_detail',
        ]);

        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/shops');

        $response->assertStatus(200);
        $shops = $response->json('data');

        // shop1: user1 の状態（want_to_go）が見える
        $shop1Data = collect($shops)->firstWhere('name', 'Shop 1');
        $this->assertTrue($shop1Data['wishlist_status']['in_wishlist']);
        $this->assertEquals('want_to_go', $shop1Data['wishlist_status']['status']);

        // shop2: user1 の状態がない（user2 の状態は見えない）
        $shop2Data = collect($shops)->firstWhere('name', 'Shop 2');
        $this->assertFalse($shop2Data['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // フィルタリングと認証の組み合わせ
    // =============================================================================

    public function test_filtered_shop_list_maintains_auth_status(): void
    {
        $user = User::factory()->create();

        $shop1 = Shop::factory()->create(['name' => 'Ramen Shop']);
        $shop2 = Shop::factory()->create(['name' => 'Cafe Shop']);

        // shop1 のみ行きたいリスト
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        $token = JWTAuth::fromUser($user);

        // name検索でフィルタ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/shops?search=Ramen');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['wishlist_status']['in_wishlist']);
        $this->assertEquals('want_to_go', $data[0]['wishlist_status']['status']);
    }

    public function test_multiple_users_see_different_wishlist(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop1 = Shop::factory()->create(['name' => 'Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Shop 2']);
        $shop3 = Shop::factory()->create(['name' => 'Shop 3']);

        // user1: shop1, shop2 を行きたい
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop1->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop2->id,
            'status' => 'visited',
            'priority' => 2,
            'source_type' => 'shop_detail',
        ]);

        // user2: shop2, shop3 を行きたい
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop2->id,
            'status' => 'want_to_go',
            'priority' => 1,
            'source_type' => 'shop_detail',
        ]);
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop3->id,
            'status' => 'visited',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        // user1 で確認
        $token1 = JWTAuth::fromUser($user1);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/shops');

        $response1->assertStatus(200);
        $shops1 = $response1->json('data');

        // user1 は shop1, shop2 だけ in_wishlist: true
        foreach ($shops1 as $shop) {
            if ($shop['name'] === 'Shop 1') {
                $this->assertTrue($shop['wishlist_status']['in_wishlist']);
                $this->assertEquals('want_to_go', $shop['wishlist_status']['status']);
            } elseif ($shop['name'] === 'Shop 2') {
                $this->assertTrue($shop['wishlist_status']['in_wishlist']);
                $this->assertEquals('visited', $shop['wishlist_status']['status']);
            } elseif ($shop['name'] === 'Shop 3') {
                $this->assertFalse($shop['wishlist_status']['in_wishlist']);
            }
        }

        // user2 で確認
        $token2 = JWTAuth::fromUser($user2);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/shops');

        $response2->assertStatus(200);
        $shops2 = $response2->json('data');

        // user2 は shop2, shop3 だけ in_wishlist: true
        foreach ($shops2 as $shop) {
            if ($shop['name'] === 'Shop 1') {
                $this->assertFalse($shop['wishlist_status']['in_wishlist']);
            } elseif ($shop['name'] === 'Shop 2') {
                $this->assertTrue($shop['wishlist_status']['in_wishlist']);
                $this->assertEquals('want_to_go', $shop['wishlist_status']['status']);
            } elseif ($shop['name'] === 'Shop 3') {
                $this->assertTrue($shop['wishlist_status']['in_wishlist']);
                $this->assertEquals('visited', $shop['wishlist_status']['status']);
            }
        }
    }
}
