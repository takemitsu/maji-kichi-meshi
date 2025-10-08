<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed categories
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_can_list_reviews(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
        ]);

        $response = $this->getJson('/api/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'rating',
                        'repeat_intention',
                        'repeat_intention_text',
                        'memo',
                        'visited_at',
                        'has_images',
                        'user',
                        'shop',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_it_can_show_single_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 5,
            'memo' => 'Great place!',
        ]);

        $response = $this->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $review->id,
                    'rating' => 5,
                    'memo' => 'Great place!',
                ],
            ]);
    }

    public function test_it_can_filter_reviews_by_shop(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        Review::factory()->create(['shop_id' => $shop1->id, 'user_id' => $user->id]);
        Review::factory()->create(['shop_id' => $shop2->id, 'user_id' => $user->id]);

        $response = $this->getJson("/api/reviews?shop_id={$shop1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($shop1->id, $data[0]['shop']['id']);
    }

    public function test_it_can_filter_reviews_by_rating(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        Review::factory()->create(['rating' => 5, 'user_id' => $user->id, 'shop_id' => $shop->id]);
        Review::factory()->create(['rating' => 3, 'user_id' => $user->id, 'shop_id' => $shop->id]);

        $response = $this->getJson('/api/reviews?rating=5');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(5, $data[0]['rating']);
    }

    public function test_it_requires_authentication_to_create_review(): void
    {
        $shop = Shop::factory()->create();

        $response = $this->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'memo' => 'Good food!',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'rating' => 4,
                    'repeat_intention' => 'yes',
                    'memo' => 'Good food!',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
        ]);
    }

    public function test_it_validates_review_creation_data(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => 999, // Invalid: non-existent shop
            'rating' => 6, // Invalid: out of range
            'repeat_intention' => 'invalid', // Invalid: not in allowed values
            'visited_at' => '2030-01-01', // Invalid: future date
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('shop_id', $errors);
        $this->assertArrayHasKey('rating', $errors);
        $this->assertArrayHasKey('repeat_intention', $errors);
        $this->assertArrayHasKey('visited_at', $errors);
    }

    public function test_it_allows_multiple_reviews_for_same_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create first review
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Create second review for same shop (should be allowed)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 3,
            'repeat_intention' => 'maybe',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'rating',
                    'repeat_intention',
                    'visited_at',
                    'user' => ['id', 'name'],
                    'shop' => ['id', 'name'],
                ],
            ]);

        // Verify both reviews exist in database
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 3,
        ]);
    }

    public function test_user_can_create_multiple_reviews_different_dates(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create first review for 2024-01-01
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-01-01',
            'memo' => 'First visit',
        ]);

        $response1->assertStatus(201);

        // Create second review for 2024-02-01
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 5,
            'repeat_intention' => 'yes',
            'visited_at' => '2024-02-01',
            'memo' => 'Second visit - even better!',
        ]);

        $response2->assertStatus(201);

        // Create third review for 2024-03-01
        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 3,
            'repeat_intention' => 'maybe',
            'visited_at' => '2024-03-01',
            'memo' => 'Third visit - not as good this time',
        ]);

        $response3->assertStatus(201);

        // Verify all three reviews exist in database
        $this->assertDatabaseCount('reviews', 3);

        // Verify each review has correct data
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'visited_at' => '2024-01-01 00:00:00',
            'memo' => 'First visit',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 5,
            'visited_at' => '2024-02-01 00:00:00',
            'memo' => 'Second visit - even better!',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 3,
            'visited_at' => '2024-03-01 00:00:00',
            'memo' => 'Third visit - not as good this time',
        ]);
    }

    public function test_multiple_reviews_appear_in_shop_review_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create multiple reviews for the same shop
        $review1 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'visited_at' => '2024-01-01 00:00:00',
            'memo' => 'First review',
        ]);

        $review2 = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 5,
            'visited_at' => '2024-02-01 00:00:00',
            'memo' => 'Second review',
        ]);

        // Get reviews for the shop
        $response = $this->getJson("/api/reviews?shop_id={$shop->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.memo', 'Second review') // Most recent first
            ->assertJsonPath('data.1.memo', 'First review');
    }

    public function test_multiple_reviews_appear_in_user_review_list(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create multiple reviews for the same shop by the same user
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'visited_at' => '2024-01-01 00:00:00',
            'memo' => 'First review',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 5,
            'visited_at' => '2024-02-01 00:00:00',
            'memo' => 'Second review',
        ]);

        // Get user's reviews
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-reviews');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.memo', 'Second review') // Most recent first
            ->assertJsonPath('data.1.memo', 'First review');
    }

    public function test_user_can_update_own_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 3,
            'memo' => 'Original memo',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/reviews/{$review->id}", [
            'rating' => 5,
            'memo' => 'Updated memo',
            'repeat_intention' => 'yes',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'rating' => 5,
                    'memo' => 'Updated memo',
                    'repeat_intention' => 'yes',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'memo' => 'Updated memo',
        ]);
    }

    public function test_user_cannot_update_others_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/reviews/{$review->id}", [
            'rating' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Review deleted successfully']);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_user_cannot_delete_others_review(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
        ]);
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(403);
    }

    public function test_it_can_get_my_reviews(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create reviews for both users
        Review::factory()->create(['user_id' => $user1->id, 'shop_id' => $shop->id]);
        Review::factory()->create(['user_id' => $user2->id, 'shop_id' => $shop->id]);

        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-reviews');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        // myReviewsでは自分のレビューのみが返され、userキーは不要
        $this->assertArrayNotHasKey('user', $data[0]);
        // 代わりに、返されたレビューが正しいことを shop で確認
        $this->assertEquals($shop->id, $data[0]['shop']['id']);
    }

    public function test_can_filter_reviews_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();

        // user1 のレビュー2件
        Review::factory(2)->create(['user_id' => $user1->id, 'shop_id' => $shop->id]);
        // user2 のレビュー1件
        Review::factory(1)->create(['user_id' => $user2->id, 'shop_id' => $shop->id]);

        $response = $this->getJson("/api/reviews?user_id={$user1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // 全てのレビューがuser1のものであることを確認
        foreach ($data as $review) {
            $this->assertEquals($user1->id, $review['user']['id']);
        }
    }

    public function test_returns_error_for_invalid_user_id(): void
    {
        $response = $this->getJson('/api/reviews?user_id=99999');

        $response->assertStatus(422);
    }

    // =============================================================================
    // Advanced Filter Tests
    // =============================================================================

    public function test_it_can_filter_reviews_by_repeat_intention(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'repeat_intention' => 'yes',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'repeat_intention' => 'no',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'repeat_intention' => 'maybe',
        ]);

        // Filter by repeat_intention = yes
        $response = $this->getJson('/api/reviews?repeat_intention=yes');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('yes', $data[0]['repeat_intention']);
    }

    public function test_it_can_filter_reviews_by_date_range(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create reviews with different dates
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-01-15',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-02-20',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => '2024-03-10',
        ]);

        // Filter by date range (2024-02-01 to 2024-02-28)
        $response = $this->getJson('/api/reviews?start_date=2024-02-01&end_date=2024-02-28');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('2024-02-20', $data[0]['visited_at']);
    }

    public function test_it_can_filter_recent_reviews_only(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create old review (60 days ago)
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => now()->subDays(60)->format('Y-m-d'),
        ]);

        // Create recent review (15 days ago)
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => now()->subDays(15)->format('Y-m-d'),
        ]);

        // Create very recent review (5 days ago)
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => now()->subDays(5)->format('Y-m-d'),
        ]);

        // Filter recent only (default 30 days)
        $response = $this->getJson('/api/reviews?recent_only=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data); // Should only return 15 and 5 days ago
    }

    public function test_it_can_filter_recent_reviews_with_custom_days(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        // Create reviews with different dates
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => now()->subDays(60)->format('Y-m-d'),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'visited_at' => now()->subDays(5)->format('Y-m-d'),
        ]);

        // Filter recent only with custom 7 days
        $response = $this->getJson('/api/reviews?recent_only=true&recent_days=7');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data); // Should only return 5 days ago
    }
}
