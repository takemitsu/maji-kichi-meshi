<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * 店舗一覧・詳細での行きたいリストの認証状態別テスト
 *
 * 前提: ログインユーザが行きたいを設定
 * A. ログインユーザでAPIコール → 正しいレスポンス（行きたい状態が返る）
 * B. 未ログインユーザでAPIコール → 正しいレスポンス（行きたい状態は返らない）
 */
class ShopWishlistAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    // =============================================================================
    // A. ログインユーザのテスト
    // =============================================================================

    public function test_authenticated_user_sees_own_wishlist_in_shop_list(): void
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

    public function test_authenticated_user_sees_own_wishlist_in_shop_detail(): void
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

    // =============================================================================
    // B. 未ログインユーザのテスト
    // =============================================================================

    public function test_guest_user_sees_no_wishlist_in_shop_list(): void
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

        // ⭐️ 重要: 行きたい状態は false（他人の状態を見せない）
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }

    public function test_guest_user_sees_no_wishlist_in_shop_detail(): void
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

        // ⭐️ 重要: 行きたい状態は false
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // 追加: 別ユーザーの行きたい状態は見えないことを確認
    // =============================================================================

    public function test_user_cannot_see_other_users_wishlist(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();

        // user1 が行きたいリストに追加
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'shop_detail',
        ]);

        // user2 でログイン
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // ⭐️ 重要: user2 には user1 の行きたい状態は見えない
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }

    public function test_authenticated_user_sees_no_wishlist_when_not_added(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 行きたいリストに追加しない

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // 行きたい状態は false
        $this->assertFalse($data['wishlist_status']['in_wishlist']);
    }
}
