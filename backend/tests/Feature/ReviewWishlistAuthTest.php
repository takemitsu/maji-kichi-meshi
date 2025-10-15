<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * レビュー一覧・詳細でのいいね＆行きたいリストの認証状態別テスト
 *
 * 前提: ログインユーザが いいね・行きたいを設定
 * A. ログインユーザでAPIコール → 正しいレスポンス（いいね数、行きたい状態が返る）
 * B. 未ログインユーザでAPIコール → 正しいレスポンス（いいね数は返るが、自分の状態は返らない）
 * C. 同一店舗に複数レビューがある場合、それぞれ正しい状態が返る
 */
class ReviewWishlistAuthTest extends TestCase
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

    public function test_authenticated_user_sees_own_likes_and_wishlist_in_review_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // いいねを追加
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 行きたいリストに追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/reviews');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // いいね数とステータスが返る
        $this->assertEquals(1, $data['likes_count']);
        $this->assertTrue($data['is_liked']);

        // 行きたいステータスが返る
        $this->assertTrue($data['shop']['wishlist_status']['in_wishlist']);
        $this->assertEquals('want_to_go', $data['shop']['wishlist_status']['status']);
        $this->assertEquals(3, $data['shop']['wishlist_status']['priority']);
    }

    public function test_authenticated_user_sees_own_likes_and_wishlist_in_review_detail(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // いいねを追加
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

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
        ])->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // いいね数とステータスが返る
        $this->assertEquals(1, $data['likes_count']);
        $this->assertTrue($data['is_liked']);

        // 行った状態が返る
        $this->assertTrue($data['shop']['wishlist_status']['in_wishlist']);
        $this->assertEquals('visited', $data['shop']['wishlist_status']['status']);
    }

    // =============================================================================
    // B. 未ログインユーザのテスト
    // =============================================================================

    public function test_guest_user_sees_likes_count_but_not_own_status_in_review_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // 他のユーザーのいいね・行きたいを追加
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        // 未ログインでアクセス
        $response = $this->getJson('/api/reviews');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // いいね数は見れる
        $this->assertEquals(1, $data['likes_count']);

        // ⭐️ 重要: 自分の状態は false（他人の状態を見せない）
        $this->assertFalse($data['is_liked']);

        // ⭐️ 重要: 行きたい状態も false（他人の状態を見せない）
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    public function test_guest_user_sees_likes_count_but_not_own_status_in_review_detail(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // 他のユーザーのいいね・行きたいを追加
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'shop_detail',
        ]);

        // 未ログインでアクセス
        $response = $this->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // いいね数は見れる
        $this->assertEquals(1, $data['likes_count']);

        // ⭐️ 重要: 自分の状態は false
        $this->assertFalse($data['is_liked']);

        // ⭐️ 重要: 行きたい状態も false
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // C. 同一店舗に複数レビューがある場合のテスト
    // =============================================================================

    public function test_multiple_reviews_for_same_shop_show_correct_wishlist_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $shop = Shop::factory()->create();

        // 同じ店舗に対して2つのレビュー
        $review1 = Review::factory()->create([
            'user_id' => $otherUser->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-01-01',
        ]);

        $review2 = Review::factory()->create([
            'user_id' => $otherUser->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-02-01',
        ]);

        // ログインユーザーが行きたいリストに追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 3,
            'source_type' => 'review',
            'source_review_id' => $review1->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/reviews?shop_id={$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);

        // ⭐️ 重要: 両方のレビューで同じ行きたいステータスが返る
        foreach ($data as $reviewData) {
            $this->assertTrue($reviewData['shop']['wishlist_status']['in_wishlist']);
            $this->assertEquals('want_to_go', $reviewData['shop']['wishlist_status']['status']);
        }
    }

    public function test_multiple_reviews_for_same_shop_guest_sees_no_wishlist(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // 同じ店舗に対して2つのレビュー
        $review1 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-01-01',
        ]);

        $review2 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-02-01',
        ]);

        // 他のユーザーが行きたいリストに追加
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'review',
            'source_review_id' => $review1->id,
        ]);

        // 未ログインでアクセス
        $response = $this->getJson("/api/reviews?shop_id={$shop->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);

        // ⭐️ 重要: 両方のレビューで行きたいステータスが false
        foreach ($data as $reviewData) {
            $this->assertFalse($reviewData['shop']['wishlist_status']['in_wishlist']);
        }
    }

    // =============================================================================
    // 追加: いいねのみ、行きたいのみのテスト
    // =============================================================================

    public function test_authenticated_user_sees_likes_without_wishlist(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // いいねのみ追加（行きたいはなし）
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/reviews');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // いいね状態は true
        $this->assertTrue($data['is_liked']);

        // 行きたい状態は false
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    public function test_authenticated_user_sees_wishlist_without_likes(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // 行きたいのみ追加（いいねはなし）
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/reviews');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // いいね状態は false
        $this->assertFalse($data['is_liked']);

        // 行きたい状態は true
        $this->assertTrue($data['shop']['wishlist_status']['in_wishlist']);
    }
}
