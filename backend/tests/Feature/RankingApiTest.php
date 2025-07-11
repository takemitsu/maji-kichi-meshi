<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RankingApiTest extends TestCase
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
    public function it_can_list_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        $publicRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'is_public' => true,
            'rank_position' => 1,
        ]);

        $privateRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'is_public' => false,
            'rank_position' => 2,
        ]);

        $response = $this->getJson('/api/rankings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'rank_position',
                        'title',
                        'description',
                        'is_public',
                        'user',
                        'shop',
                        'category',
                    ],
                ],
                'links',
                'meta',
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($publicRanking->id, $data[0]['id']);
        $this->assertTrue($data[0]['is_public']);
    }

    /** @test */
    public function it_can_show_public_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_public' => true,
            'rank_position' => 1,
        ]);

        $response = $this->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $ranking->id,
                    'rank_position' => 1,
                    'is_public' => true,
                ],
            ]);
    }

    /** @test */
    public function it_hides_private_ranking_from_unauthorized_users()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_filter_rankings_by_category()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category1 = Category::first();
        $category2 = Category::skip(1)->first();

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category1->id,
            'is_public' => true,
        ]);

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category2->id,
            'is_public' => true,
        ]);

        $response = $this->getJson("/api/rankings?category_id={$category1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($category1->id, $data[0]['category']['id']);
    }

    /** @test */
    public function it_can_filter_rankings_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();

        Ranking::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        Ranking::factory()->create([
            'user_id' => $user2->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        $response = $this->getJson("/api/rankings?user_id={$user1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user']['id']);
    }

    /** @test */
    public function it_requires_authentication_to_create_ranking()
    {
        $shop = Shop::factory()->create();
        $category = Category::first();

        $response = $this->postJson('/api/rankings', [
            'title' => 'Test Ranking',
            'description' => 'Test description',
            'category_id' => $category->id,
            'shops' => [
                [
                    'shop_id' => $shop->id,
                    'position' => 1,
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_create_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', [
            'title' => 'My favorite shop',
            'description' => 'This is my number one choice',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                [
                    'shop_id' => $shop->id,
                    'position' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'is_public' => true,
                    'title' => 'My favorite shop',
                    'description' => 'This is my number one choice',
                ],
            ]);

        // ランキング作成後のデータベース確認
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'title' => 'My favorite shop',
            'description' => 'This is my number one choice',
        ]);
    }

    /** @test */
    public function it_validates_ranking_creation_data()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', [
            'title' => 'Test',
            'category_id' => 999, // Invalid: non-existent category
            'shops' => [
                [
                    'shop_id' => 999, // Invalid: non-existent shop
                    'position' => 0, // Invalid: position must be >= 1
                ],
            ],
        ]);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('shops.0.shop_id', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('shops.0.position', $errors);
    }

    /** @test */
    public function it_adjusts_positions_when_inserting_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // Create rankings at positions 1 and 2
        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
        ]);

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
        ]);

        // Insert new ranking at position 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', [
            'title' => 'New ranking',
            'category_id' => $category->id,
            'shops' => [
                [
                    'shop_id' => $shop3->id,
                    'position' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);

        // Check that old rankings were replaced with new ranking
        $rankings = Ranking::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->ordered()
            ->get();

        // Only one ranking should remain (the new one)
        $this->assertCount(1, $rankings);
        $this->assertEquals($shop3->id, $rankings[0]->shop_id); // New shop at position 1
    }

    /** @test */
    public function user_can_update_own_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'is_public' => false,
            'title' => 'Original title',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'rank_position' => 2,
            'is_public' => true,
            'title' => 'Updated title',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'rank_position' => 2,
                    'is_public' => true,
                    'title' => 'Updated title',
                ],
            ]);

        $this->assertDatabaseHas('rankings', [
            'id' => $ranking->id,
            'rank_position' => 2,
            'is_public' => true,
        ]);
    }

    /** @test */
    public function user_cannot_update_others_ranking()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
        ]);
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => 'Hacked title',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function user_can_delete_own_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/rankings/{$ranking1->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ranking deleted successfully']);

        $this->assertDatabaseMissing('rankings', ['id' => $ranking1->id]);

        // Check that remaining ranking position was adjusted
        $ranking2->refresh();
        $this->assertEquals(1, $ranking2->rank_position);
    }

    /** @test */
    public function user_cannot_delete_others_ranking()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
        ]);
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function it_can_get_my_rankings()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        // Create rankings for both users
        Ranking::factory()->create([
            'user_id' => $user1->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
        ]);

        Ranking::factory()->create([
            'user_id' => $user2->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
        ]);

        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-rankings');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user']['id']);
    }

    /** @test */
    public function it_can_get_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/public-rankings');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_public']);
    }

    /** @test */
    public function owner_can_view_own_private_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $ranking->id,
                    'is_public' => false,
                ],
            ]);
    }
}
