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
 * レビューAPIの認証統合テスト
 *
 * レビュー一覧・詳細でのいいね・行きたいリストの表示を、
 * 認証状態別に検証する統合テスト
 */
class ReviewApiAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    // =============================================================================
    // レビュー一覧でのいいね・行きたい表示テスト
    // =============================================================================

    public function test_review_list_shows_likes_and_wishlist_for_authenticated_user(): void
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

    public function test_review_list_hides_personal_status_for_guest(): void
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

        // 自分の状態は false（他人の状態を見せない）
        $this->assertFalse($data['is_liked']);

        // 行きたい状態も false（他人の状態を見せない）
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // レビュー詳細でのいいね・行きたい表示テスト
    // =============================================================================

    public function test_review_detail_shows_likes_and_wishlist_for_authenticated_user(): void
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

    public function test_review_detail_hides_personal_status_for_guest(): void
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

        // 自分の状態は false
        $this->assertFalse($data['is_liked']);

        // 行きたい状態も false
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // 複数のいいね・行きたいユーザーがいる場合
    // =============================================================================

    public function test_review_list_shows_correct_counts_with_multiple_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
        ]);

        // 3人のユーザーがいいね
        ReviewLike::create(['user_id' => $user1->id, 'review_id' => $review->id]);
        ReviewLike::create(['user_id' => $user2->id, 'review_id' => $review->id]);
        ReviewLike::create(['user_id' => $user3->id, 'review_id' => $review->id]);

        // user1 が行きたいリストに追加
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        // user2 が行きたいリストに追加（行った状態）
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/reviews');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        // いいね数は3（全員分）
        $this->assertEquals(3, $data['likes_count']);

        // user1 の状態
        $this->assertTrue($data['is_liked']);
        $this->assertTrue($data['shop']['wishlist_status']['in_wishlist']);
        $this->assertEquals('want_to_go', $data['shop']['wishlist_status']['status']);
    }

    public function test_review_detail_shows_only_own_status_not_others(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
        ]);

        // user2 がいいね・行きたいを追加
        ReviewLike::create(['user_id' => $user2->id, 'review_id' => $review->id]);
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop->id,
            'status' => 'visited',
            'source_type' => 'review',
            'source_review_id' => $review->id,
        ]);

        // user1 でログイン（いいね・行きたいなし）
        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // いいね数は1（user2の分）
        $this->assertEquals(1, $data['likes_count']);

        // user1 の状態は false（user2 の状態は見えない）
        $this->assertFalse($data['is_liked']);
        $this->assertFalse($data['shop']['wishlist_status']['in_wishlist']);
    }

    // =============================================================================
    // フィルタリングと認証の組み合わせ
    // =============================================================================

    public function test_filtered_review_list_maintains_auth_status(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        $review1 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'rating' => 5,
        ]);

        $review2 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'rating' => 3,
        ]);

        // review1 のみいいね
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $review1->id]);

        // shop1 のみ行きたいリスト
        Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'status' => 'want_to_go',
            'source_type' => 'shop_detail',
        ]);

        $token = JWTAuth::fromUser($user);

        // rating=5 でフィルタ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/reviews?rating=5');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_liked']);
        $this->assertTrue($data[0]['shop']['wishlist_status']['in_wishlist']);
    }

    public function test_multiple_users_see_different_likes_and_wishlist(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        $review1 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop1->id,
            'memo' => 'Review 1',
        ]);
        $review2 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop2->id,
            'memo' => 'Review 2',
        ]);

        // user1: review1 をいいね、shop1 を行きたい
        ReviewLike::create(['user_id' => $user1->id, 'review_id' => $review1->id]);
        Wishlist::create([
            'user_id' => $user1->id,
            'shop_id' => $shop1->id,
            'status' => 'want_to_go',
            'source_type' => 'review',
            'source_review_id' => $review1->id,
        ]);

        // user2: review2 をいいね、shop2 を行きたい
        ReviewLike::create(['user_id' => $user2->id, 'review_id' => $review2->id]);
        Wishlist::create([
            'user_id' => $user2->id,
            'shop_id' => $shop2->id,
            'status' => 'want_to_go',
            'source_type' => 'review',
            'source_review_id' => $review2->id,
        ]);

        // user1 で確認
        $token1 = JWTAuth::fromUser($user1);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/reviews');

        $response1->assertStatus(200);
        $reviews1 = $response1->json('data');

        // user1 は review1 だけいいね、shop1 だけ行きたい
        foreach ($reviews1 as $review) {
            if ($review['memo'] === 'Review 1') {
                $this->assertTrue($review['is_liked']);
                $this->assertTrue($review['shop']['wishlist_status']['in_wishlist']);
            } elseif ($review['memo'] === 'Review 2') {
                $this->assertFalse($review['is_liked']);
                $this->assertFalse($review['shop']['wishlist_status']['in_wishlist']);
            }
        }

        // user2 で確認
        $token2 = JWTAuth::fromUser($user2);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/reviews');

        $response2->assertStatus(200);
        $reviews2 = $response2->json('data');

        // user2 は review2 だけいいね、shop2 だけ行きたい
        foreach ($reviews2 as $review) {
            if ($review['memo'] === 'Review 1') {
                $this->assertFalse($review['is_liked']);
                $this->assertFalse($review['shop']['wishlist_status']['in_wishlist']);
            } elseif ($review['memo'] === 'Review 2') {
                $this->assertTrue($review['is_liked']);
                $this->assertTrue($review['shop']['wishlist_status']['in_wishlist']);
            }
        }
    }
}
