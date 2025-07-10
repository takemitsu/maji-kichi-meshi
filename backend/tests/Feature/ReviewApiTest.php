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

    /** @test */
    public function it_can_list_reviews()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'また行く',
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

    /** @test */
    public function it_can_show_single_review()
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

    /** @test */
    public function it_can_filter_reviews_by_shop()
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

    /** @test */
    public function it_can_filter_reviews_by_rating()
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

    /** @test */
    public function it_requires_authentication_to_create_review()
    {
        $shop = Shop::factory()->create();

        $response = $this->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'また行く',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_create_review()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'また行く',
            'memo' => 'Good food!',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'rating' => 4,
                    'repeat_intention' => 'また行く',
                    'memo' => 'Good food!',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 4,
        ]);
    }

    /** @test */
    public function it_validates_review_creation_data()
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

        $errors = $response->json('messages');
        $this->assertArrayHasKey('shop_id', $errors);
        $this->assertArrayHasKey('rating', $errors);
        $this->assertArrayHasKey('repeat_intention', $errors);
        $this->assertArrayHasKey('visited_at', $errors);
    }

    /** @test */
    public function it_prevents_duplicate_reviews_for_same_shop()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create first review
        Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        // Try to create second review for same shop
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 3,
            'repeat_intention' => 'わからん',
            'visited_at' => '2024-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'You have already reviewed this shop. Please update your existing review instead.',
            ]);
    }

    /** @test */
    public function user_can_update_own_review()
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
            'repeat_intention' => 'また行く',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'rating' => 5,
                    'memo' => 'Updated memo',
                    'repeat_intention' => 'また行く',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'memo' => 'Updated memo',
        ]);
    }

    /** @test */
    public function user_cannot_update_others_review()
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

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function user_can_delete_own_review()
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

    /** @test */
    public function user_cannot_delete_others_review()
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

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function it_can_get_my_reviews()
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
        $this->assertEquals($user1->id, $data[0]['user']['id']);
    }
}
