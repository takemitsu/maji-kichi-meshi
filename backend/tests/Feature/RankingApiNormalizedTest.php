<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class RankingApiNormalizedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed categories
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_can_list_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        // Create public ranking with shops
        $publicRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Public Ranking',
        ]);
        $publicRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);
        $publicRanking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        // Create private ranking
        $privateRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
            'title' => 'Private Ranking',
        ]);
        $privateRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $response = $this->getJson('/api/rankings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'is_public',
                        'user',
                        'category',
                        'shops' => [
                            '*' => [
                                'id',
                                'name',
                                'rank_position',
                            ],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($publicRanking->id, $data[0]['id']);
        $this->assertTrue($data[0]['is_public']);
        $this->assertCount(2, $data[0]['shops']);
    }

    public function test_it_can_show_public_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Test Ranking',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        $response = $this->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'is_public',
                    'shops' => [
                        '*' => [
                            'id',
                            'rank_position',
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json('data');
        $this->assertTrue($responseData['is_public']);
        $this->assertCount(2, $responseData['shops']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
        $this->assertEquals(2, $responseData['shops'][1]['rank_position']);
    }

    public function test_authenticated_user_can_create_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', [
            'title' => 'My favorite shops',
            'description' => 'Top 2 choices',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                [
                    'shop_id' => $shop1->id,
                    'position' => 1,
                ],
                [
                    'shop_id' => $shop2->id,
                    'position' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'My favorite shops',
                    'description' => 'Top 2 choices',
                    'is_public' => true,
                ],
            ]);

        // Check database
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'title' => 'My favorite shops',
            'description' => 'Top 2 choices',
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);
    }

    public function test_it_validates_max_10_shops_limit()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $category = Category::first();

        // Create 11 shops
        $shops = Shop::factory()->count(11)->create();

        $data = [
            'title' => 'Test Ranking with Too Many Shops',
            'description' => 'This should fail',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => $shops->map(function ($shop, $index) {
                return [
                    'shop_id' => $shop->id,
                    'position' => $index + 1,
                ];
            })->toArray(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', $data);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('shops', $errors);
        $this->assertStringContainsString('10 items', $errors['shops'][0]);
    }

    public function test_user_can_update_own_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // Create ranking
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
            'title' => 'Original title',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        // Update ranking
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => 'Updated title',
            'is_public' => true,
            'shops' => [
                [
                    'shop_id' => $shop2->id,
                    'position' => 1,
                ],
                [
                    'shop_id' => $shop3->id,
                    'position' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'is_public' => true,
                ],
                'message' => 'Ranking updated successfully',
            ]);

        // Check database
        $this->assertDatabaseHas('rankings', [
            'id' => $ranking->id,
            'title' => 'Updated title',
            'is_public' => true,
        ]);

        // Old item should be deleted
        $this->assertDatabaseMissing('ranking_items', [
            'ranking_id' => $ranking->id,
            'shop_id' => $shop1->id,
        ]);

        // New items should exist
        $this->assertDatabaseHas('ranking_items', [
            'ranking_id' => $ranking->id,
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'ranking_id' => $ranking->id,
            'shop_id' => $shop3->id,
            'rank_position' => 2,
        ]);
    }

    public function test_user_can_delete_own_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Ranking',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ranking deleted successfully']);

        $this->assertDatabaseMissing('rankings', ['id' => $ranking->id]);
        $this->assertDatabaseMissing('ranking_items', ['ranking_id' => $ranking->id]);
    }

    public function test_it_can_get_my_rankings()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        // Create rankings for both users
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'title' => 'User 1 Ranking',
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'title' => 'User 2 Ranking',
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        $token = JWTAuth::fromUser($user1);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-rankings');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user']['id']);
        $this->assertEquals('User 1 Ranking', $data[0]['title']);
    }

    public function test_it_can_get_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        $publicRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Public Ranking',
        ]);
        $publicRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $privateRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
            'title' => 'Private Ranking',
        ]);
        $privateRanking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        $response = $this->getJson('/api/public-rankings');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_public']);
        $this->assertEquals('Public Ranking', $data[0]['title']);
    }
}
