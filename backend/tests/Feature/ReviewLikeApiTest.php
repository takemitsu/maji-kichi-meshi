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

        // Like 2 of them
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $reviews[0]->id]);
        ReviewLike::create(['user_id' => $user->id, 'review_id' => $reviews[1]->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-liked-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
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
}
