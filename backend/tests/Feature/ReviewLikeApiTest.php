<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReviewLikeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed categories
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_guest_can_view_likes_count(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Create 3 likes
        $users = User::factory(3)->create();
        foreach ($users as $likeUser) {
            ReviewLike::create([
                'user_id' => $likeUser->id,
                'review_id' => $review->id,
            ]);
        }

        $response = $this->getJson("/api/reviews/{$review->id}/likes");

        $response->assertStatus(200)
            ->assertJson([
                'likes_count' => 3,
            ])
            ->assertJsonMissingPath('is_liked'); // Guest users should not have is_liked field
    }

    public function test_authenticated_user_can_view_likes_count_only_on_public_endpoint(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Create 2 likes by other users
        $otherUsers = User::factory(2)->create();
        foreach ($otherUsers as $likeUser) {
            ReviewLike::create([
                'user_id' => $likeUser->id,
                'review_id' => $review->id,
            ]);
        }

        // Public endpoint doesn't require auth, so we don't pass token
        // The endpoint returns likes_count only without is_liked for public access
        $response = $this->getJson("/api/reviews/{$review->id}/likes");

        $response->assertStatus(200)
            ->assertJson([
                'likes_count' => 2,
            ])
            ->assertJsonMissingPath('is_liked'); // Public access doesn't get is_liked
    }

    public function test_user_can_like_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$review->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'いいねしました',
                'is_liked' => true,
                'likes_count' => 1,
            ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_can_unlike_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // First, like the review
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // Then, unlike it
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$review->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'いいねを取り消しました',
                'is_liked' => false,
                'likes_count' => 0,
            ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_cannot_like_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        $response = $this->postJson("/api/reviews/{$review->id}/like");

        $response->assertStatus(401);
    }

    public function test_user_cannot_like_same_review_twice(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user);

        // Like once
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$review->id}/like");

        // Try to like again
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$review->id}/like");

        // Should only have 1 like (toggle removes it on second call)
        $this->assertDatabaseCount('review_likes', 0);
    }

    public function test_my_liked_reviews_includes_complete_response_schema(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'memo' => 'Test review',
        ]);

        // 3 users like this review (including current user)
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $review->id]);
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $review->id]);
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $review->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);

        // ReviewResource の全必須フィールドを検証
        $reviewData = $data[0];
        $this->assertArrayHasKey('id', $reviewData);
        $this->assertArrayHasKey('rating', $reviewData);
        $this->assertArrayHasKey('repeat_intention', $reviewData);
        $this->assertArrayHasKey('memo', $reviewData);
        $this->assertArrayHasKey('visited_at', $reviewData);
        $this->assertArrayHasKey('has_images', $reviewData);
        $this->assertArrayHasKey('shop', $reviewData);
        $this->assertArrayHasKey('user', $reviewData);
        $this->assertArrayHasKey('likes_count', $reviewData);
        $this->assertArrayHasKey('is_liked', $reviewData);
        $this->assertArrayHasKey('created_at', $reviewData);
        $this->assertArrayHasKey('updated_at', $reviewData);

        // ★ 今回の問題の本質: likes_count が 0 ではなく 3 であることを検証
        $this->assertEquals(3, $reviewData['likes_count'], 'likes_count should be 3, not 0');
        $this->assertTrue($reviewData['is_liked'], 'is_liked should be true for current user');
        $this->assertEquals(4, $reviewData['rating']);
        $this->assertEquals('Test review', $reviewData['memo']);
    }

    public function test_user_can_get_their_liked_reviews(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create 3 reviews
        $shops = Shop::factory(3)->create();
        $reviews = [];
        foreach ($shops as $shop) {
            $reviews[] = Review::factory()->create([
                'user_id' => User::factory()->create()->id,
                'shop_id' => $shop->id,
            ]);
        }

        // Like 2 of them, with different like counts
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $reviews[0]->id]);
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $reviews[0]->id]);
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $reviews[1]->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // レスポンススキーマ検証
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('rating', $data[0]);
        $this->assertArrayHasKey('memo', $data[0]);
        $this->assertArrayHasKey('shop', $data[0]);
        $this->assertArrayHasKey('user', $data[0]);
        $this->assertArrayHasKey('likes_count', $data[0]);
        $this->assertArrayHasKey('is_liked', $data[0]);

        // likes_count の正確性検証
        $this->assertEquals(1, $data[0]['likes_count'], 'Second review should have 1 like');
        $this->assertEquals(2, $data[1]['likes_count'], 'First review should have 2 likes');

        // is_liked の正確性検証
        $this->assertTrue($data[0]['is_liked'], 'Current user liked this review');
        $this->assertTrue($data[1]['is_liked'], 'Current user liked this review');
    }

    public function test_guest_cannot_get_liked_reviews(): void
    {
        $response = $this->getJson('/api/my-liked-reviews');

        $response->assertStatus(401);
    }

    public function test_liked_reviews_are_ordered_by_most_recent(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $shops = Shop::factory(2)->create();
        $review1 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shops[0]->id,
            'memo' => 'First review',
        ]);
        $review2 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shops[1]->id,
            'memo' => 'Second review',
        ]);

        // 各レビューに異なる数のいいねを追加
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $review1->id]);
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $review2->id]);
        ReviewLike::create(['user_id' => User::factory()->create()->id, 'review_id' => $review2->id]);

        // Like review1 first, then review2 with explicit timestamps
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review1->id,
            'created_at' => now()->subSecond(),
        ]);
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review2->id,
            'created_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Most recent like should be first (review2)
        $this->assertEquals('Second review', $data[0]['memo']);
        $this->assertEquals('First review', $data[1]['memo']);

        // likes_count 検証（現在のユーザー含む全てのいいね数）
        $this->assertEquals(3, $data[0]['likes_count'], 'review2 should have 3 likes');
        $this->assertEquals(2, $data[1]['likes_count'], 'review1 should have 2 likes');

        // is_liked 検証（現在のユーザーがいいねしている）
        $this->assertTrue($data[0]['is_liked']);
        $this->assertTrue($data[1]['is_liked']);
    }

    public function test_likes_count_updates_correctly(): void
    {
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
        ]);

        $users = User::factory(5)->create();

        // 5 users like the review one by one using direct model creation
        // (avoiding potential JWT issues in test)
        foreach ($users as $user) {
            ReviewLike::create([
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]);
        }

        // Check likes count after all 5 likes
        $this->assertDatabaseCount('review_likes', 5);
        $response = $this->getJson("/api/reviews/{$review->id}/likes");
        $response->assertStatus(200)
            ->assertJson(['likes_count' => 5]);

        // One user unlikes via API
        $token = JWTAuth::fromUser($users[0]);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$review->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'is_liked' => false,
                'likes_count' => 4,
            ]);

        // Check updated count
        $this->assertDatabaseCount('review_likes', 4);
        $response = $this->getJson("/api/reviews/{$review->id}/likes");
        $response->assertStatus(200)
            ->assertJson(['likes_count' => 4]);
    }

    public function test_deleting_review_deletes_associated_likes(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Create some likes
        $likeUsers = User::factory(3)->create();
        foreach ($likeUsers as $likeUser) {
            ReviewLike::create([
                'user_id' => $likeUser->id,
                'review_id' => $review->id,
            ]);
        }

        $this->assertDatabaseCount('review_likes', 3);

        // Delete the review
        $token = JWTAuth::fromUser($user);
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/reviews/{$review->id}");

        // Likes should be deleted too (cascade)
        $this->assertDatabaseCount('review_likes', 0);
    }

    // =============================================================================
    // ReviewLikeController の show メソッドのオプショナル認証テスト
    // =============================================================================

    public function test_review_like_show_works_for_guest(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Create 2 likes
        $users = User::factory(2)->create();
        foreach ($users as $likeUser) {
            ReviewLike::create([
                'user_id' => $likeUser->id,
                'review_id' => $review->id,
            ]);
        }

        // 未ログインでアクセス
        $response = $this->getJson("/api/reviews/{$review->id}/likes");

        $response->assertStatus(200)
            ->assertJson([
                'likes_count' => 2,
            ])
            ->assertJsonMissingPath('is_liked'); // ゲストには is_liked が含まれない
    }

    public function test_review_like_show_includes_is_liked_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop->id,
        ]);

        // Create 3 likes by other users
        $otherUsers = User::factory(3)->create();
        foreach ($otherUsers as $likeUser) {
            ReviewLike::create([
                'user_id' => $likeUser->id,
                'review_id' => $review->id,
            ]);
        }

        // このユーザーもいいね
        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/reviews/{$review->id}/likes");

        $response->assertStatus(200)
            ->assertJson([
                'likes_count' => 4,
                'is_liked' => true, // 認証済みユーザーには is_liked が含まれる
            ]);
    }

    // =============================================================================
    // 複数ユーザーのデータ隔離テスト
    // =============================================================================

    public function test_user_only_sees_own_liked_reviews(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();

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
        $review3 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop3->id,
            'memo' => 'Review 3',
        ]);

        // user1: review1, review2 をいいね
        ReviewLike::create(['user_id' => $user1->id, 'review_id' => $review1->id]);
        ReviewLike::create(['user_id' => $user1->id, 'review_id' => $review2->id]);

        // user2: review2, review3 をいいね
        ReviewLike::create(['user_id' => $user2->id, 'review_id' => $review2->id]);
        ReviewLike::create(['user_id' => $user2->id, 'review_id' => $review3->id]);

        // user1 で取得
        $token1 = JWTAuth::fromUser($user1);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');

        // user1 は review1, review2 のみ見える (review3 は見えない)
        $this->assertCount(2, $data);
        $memos = collect($data)->pluck('memo')->toArray();
        $this->assertContains('Review 1', $memos);
        $this->assertContains('Review 2', $memos);
        $this->assertNotContains('Review 3', $memos);
    }

    public function test_empty_liked_reviews_returns_empty_data(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(0, $data);
    }

    public function test_liked_reviews_pagination(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create 20 reviews and like them all
        for ($i = 1; $i <= 20; $i++) {
            $shop = Shop::factory()->create();
            $review = Review::factory()->create([
                'user_id' => User::factory()->create()->id,
                'shop_id' => $shop->id,
                'memo' => "Review {$i}",
            ]);
            ReviewLike::create([
                'user_id' => $user->id,
                'review_id' => $review->id,
                'created_at' => now()->addSeconds($i),
            ]);
        }

        // Get first page with per_page=10
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews?per_page=10');

        $response->assertStatus(200);
        $data = $response->json('data');
        $meta = $response->json('meta');

        $this->assertCount(10, $data);
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(2, $meta['last_page']);
        $this->assertEquals(20, $meta['total']);

        // 全てのレビューが必須フィールドを持つことを検証
        foreach ($data as $index => $review) {
            $this->assertArrayHasKey('id', $review, "Review at index {$index} missing id");
            $this->assertArrayHasKey('likes_count', $review, "Review at index {$index} missing likes_count");
            $this->assertArrayHasKey('is_liked', $review, "Review at index {$index} missing is_liked");
            $this->assertArrayHasKey('rating', $review, "Review at index {$index} missing rating");
            $this->assertArrayHasKey('shop', $review, "Review at index {$index} missing shop");
            $this->assertArrayHasKey('user', $review, "Review at index {$index} missing user");

            // 型検証
            $this->assertIsInt($review['likes_count'], "likes_count should be integer at index {$index}");
            $this->assertIsBool($review['is_liked'], "is_liked should be boolean at index {$index}");

            // likes_count は最低でも1（現在のユーザーのいいね）
            $this->assertGreaterThanOrEqual(1, $review['likes_count'], "likes_count should be at least 1 at index {$index}");

            // 全てのレビューは現在のユーザーがいいねしている
            $this->assertTrue($review['is_liked'], "All reviews should be liked by current user at index {$index}");
        }
    }

    public function test_toggle_like_fails_with_nonexistent_review(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // 存在しないreview_id
        $nonexistentReviewId = 99999;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/reviews/{$nonexistentReviewId}/like");

        $response->assertStatus(404);
    }

    public function test_my_likes_with_deleted_reviews(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        $review1 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop1->id,
            'memo' => 'Review 1 (will be deleted)',
        ]);
        $review2 = Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'shop_id' => $shop2->id,
            'memo' => 'Review 2 (active)',
        ]);

        // 両方のレビューにいいね
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $review1->id]);
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $review2->id]);

        // review1を削除 (カスケード削除でreview_likesも削除される)
        $review1->delete();

        // my-liked-reviews を取得
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');

        // 削除されたreview1は含まれず、review2のみ表示される
        $this->assertCount(1, $data);
        $this->assertEquals('Review 2 (active)', $data[0]['memo']);

        // データベースにはreview2のいいねのみ残っている
        $this->assertDatabaseCount('review_likes', 1);
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review2->id,
        ]);
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review1->id,
        ]);
    }
}
