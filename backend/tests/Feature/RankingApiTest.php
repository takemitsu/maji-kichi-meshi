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

    public function test_it_can_list_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        $publicRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $publicRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $privateRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $privateRanking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
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
    }

    public function test_it_can_show_public_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Test Ranking',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
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
        $this->assertCount(1, $responseData['shops']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
    }

    public function test_it_hides_private_ranking_from_unauthorized_users()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $response = $this->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(404);
    }

    public function test_it_can_filter_rankings_by_category()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category1 = Category::first();
        $category2 = Category::skip(1)->first();

        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'is_public' => true,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'is_public' => true,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $response = $this->getJson("/api/rankings?category_id={$category1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($category1->id, $data[0]['category']['id']);
    }

    public function test_it_can_filter_rankings_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::first();

        // user1のランキング1件
        Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        // user2のランキング1件
        Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);

        $response = $this->getJson("/api/rankings?user_id={$user1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user']['id']);
    }

    public function test_it_requires_authentication_to_create_ranking()
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

    public function test_authenticated_user_can_create_ranking()
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

    public function test_it_validates_ranking_creation_data()
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

    public function test_it_adjusts_positions_when_inserting_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // Create rankings at positions 1 and 2
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
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

        // Check that new ranking was created
        $rankings = Ranking::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->get();

        // Should have 3 rankings total now
        $this->assertCount(3, $rankings);
        // Verify the new ranking has the shop at position 1
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop3->id,
            'rank_position' => 1,
        ]);
    }

    public function test_user_can_update_own_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
            'title' => 'Original title',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => 'Updated title',
            'is_public' => true,
            'shops' => [
                [
                    'shop_id' => $shop->id,
                    'position' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_public' => true,
                    'title' => 'Updated title',
                ],
                'message' => 'Ranking updated successfully',
            ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'is_public' => true,
            'title' => 'Updated title',
        ]);
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop->id,
            'rank_position' => 2,
        ]);
    }

    public function test_user_cannot_update_others_ranking()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
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

    public function test_user_can_delete_own_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/rankings/{$ranking1->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ranking deleted successfully']);

        $this->assertDatabaseMissing('rankings', ['id' => $ranking1->id]);

        // Check that remaining ranking exists
        $this->assertDatabaseHas('rankings', ['id' => $ranking2->id]);
    }

    public function test_user_cannot_delete_others_ranking()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);
        $token = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);
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
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
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
    }

    public function test_it_can_get_public_rankings()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        $response = $this->getJson('/api/public-rankings');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_public']);
    }

    public function test_owner_can_view_own_private_ranking()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$ranking->id}");

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
        $this->assertFalse($responseData['is_public']);
    }

    public function test_it_creates_multiple_shops_ranking_and_returns_all_shops()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $data = [
            'title' => 'Multiple Shops Test Ranking',
            'description' => 'Test ranking with multiple shops',
            'category_id' => $category->id,
            'is_public' => false,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => 1],
                ['shop_id' => $shop2->id, 'position' => 2],
                ['shop_id' => $shop3->id, 'position' => 3],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'is_public',
                    'user' => ['id', 'name', 'email'],
                    'category' => ['id', 'name'],
                    'shops' => [
                        '*' => [
                            'id',
                            'name',
                            'rank_position',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        $responseData = $response->json('data');
        $this->assertCount(3, $responseData['shops']);

        // Check that all shops are returned in correct order
        $this->assertEquals($shop1->id, $responseData['shops'][0]['id']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
        $this->assertEquals($shop2->id, $responseData['shops'][1]['id']);
        $this->assertEquals(2, $responseData['shops'][1]['rank_position']);
        $this->assertEquals($shop3->id, $responseData['shops'][2]['id']);
        $this->assertEquals(3, $responseData['shops'][2]['rank_position']);

        // Verify database records
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Multiple Shops Test Ranking',
        ]);
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop3->id,
            'rank_position' => 3,
        ]);
    }

    public function test_it_creates_single_shop_ranking_and_returns_array()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $data = [
            'title' => 'Single Shop Test Ranking',
            'description' => 'Test ranking with single shop',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                ['shop_id' => $shop->id, 'position' => 1],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'is_public',
                    'user' => ['id', 'name', 'email'],
                    'category' => ['id', 'name'],
                    'shops' => [
                        '*' => [
                            'id',
                            'name',
                            'rank_position',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData['shops']);
        $this->assertEquals($shop->id, $responseData['shops'][0]['id']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
    }

    public function test_index_returns_multiple_shops_for_same_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        // Create one ranking with multiple shops
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Multiple Index',
            'is_public' => true,
        ]);

        // Add multiple items to the ranking
        $ranking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        $response = $this->getJson('/api/rankings');

        $response->assertStatus(200);
        $responseData = $response->json('data');

        // Should return the ranking
        $this->assertCount(1, $responseData);
        $returnedRanking = $responseData[0];
        $this->assertEquals('Test Multiple Index', $returnedRanking['title']);

        // Check that we have both shops in the shops array
        $this->assertCount(2, $returnedRanking['shops']);
        $shopIds = collect($returnedRanking['shops'])->pluck('id');
        $this->assertTrue($shopIds->contains($shop1->id));
        $this->assertTrue($shopIds->contains($shop2->id));
    }

    public function test_show_returns_individual_ranking_properly()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Show Single',
            'is_public' => false,
        ]);

        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'is_public',
                    'user' => ['id', 'name', 'email'],
                    'category' => ['id', 'name'],
                    'shops' => [
                        '*' => [
                            'id',
                            'name',
                            'rank_position',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);

        $responseData = $response->json('data');
        $this->assertEquals($ranking->title, $responseData['title']);
        $this->assertCount(1, $responseData['shops']);
        $this->assertEquals($shop->id, $responseData['shops'][0]['id']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
    }

    public function test_update_from_single_to_multiple_shops_works()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // First create a single shop ranking
        $data = [
            'title' => 'Update Test Ranking',
            'description' => 'Initially single shop',
            'category_id' => $category->id,
            'is_public' => false,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => 1],
            ],
        ];

        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', $data);

        $createResponse->assertStatus(201);
        $rankingId = $createResponse->json('data.id');

        // Update to include multiple shops
        $updateData = [
            'title' => 'Updated Multiple Shops Ranking',
            'description' => 'Now with multiple shops',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => 1],
                ['shop_id' => $shop2->id, 'position' => 2],
                ['shop_id' => $shop3->id, 'position' => 3],
            ],
        ];

        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$rankingId}", $updateData);

        $updateResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'is_public',
                    'user' => ['id', 'name', 'email'],
                    'category' => ['id', 'name'],
                    'shops' => [
                        '*' => [
                            'id',
                            'name',
                            'rank_position',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
                'message',
            ]);

        $responseData = $updateResponse->json('data');
        $this->assertCount(3, $responseData['shops']);

        // Check that all shops are returned in correct order
        $this->assertEquals($shop1->id, $responseData['shops'][0]['id']);
        $this->assertEquals(1, $responseData['shops'][0]['rank_position']);
        $this->assertEquals($shop2->id, $responseData['shops'][1]['id']);
        $this->assertEquals(2, $responseData['shops'][1]['rank_position']);
        $this->assertEquals($shop3->id, $responseData['shops'][2]['id']);
        $this->assertEquals(3, $responseData['shops'][2]['rank_position']);

        // Verify old ranking was deleted and new ones created
        $this->assertDatabaseMissing('rankings', [
            'user_id' => $user->id,
            'title' => 'Update Test Ranking',
        ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Updated Multiple Shops Ranking',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop2->id,
            'rank_position' => 2,
        ]);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop3->id,
            'rank_position' => 3,
        ]);

        // Verify updated rankings can be retrieved via index
        $indexResponse = $this->getJson('/api/rankings');
        $indexResponse->assertStatus(200);
        $indexData = $indexResponse->json('data');

        $updatedRankings = collect($indexData)->where('title', 'Updated Multiple Shops Ranking');
        $this->assertEquals(1, $updatedRankings->count()); // Should be 1 grouped ranking

        // Verify the grouped ranking has 3 shops
        $groupedRanking = $updatedRankings->first();
        $this->assertCount(3, $groupedRanking['shops']);

        // Verify individual ranking can still be retrieved
        $newRankingId = $responseData['id'];
        $showResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$newRankingId}");

        $showResponse->assertStatus(200);
        $this->assertEquals('Updated Multiple Shops Ranking', $showResponse->json('data.title'));
    }

    public function test_my_rankings_returns_multiple_shops_for_same_title()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // Create one ranking with multiple shops
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'My Multiple Shops Ranking',
            'is_public' => false,
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
        ])->getJson('/api/my-rankings');

        $response->assertStatus(200);
        $responseData = $response->json('data');

        // Should return the ranking
        $myRankings = collect($responseData)->where('title', 'My Multiple Shops Ranking');
        $this->assertEquals(1, $myRankings->count());

        $returnedRanking = $myRankings->first();
        $this->assertCount(2, $returnedRanking['shops']);

        // Check that we have rankings for both shops
        $shopIds = collect($returnedRanking['shops'])->pluck('id');
        $this->assertTrue($shopIds->contains($shop1->id));
        $this->assertTrue($shopIds->contains($shop2->id));
    }

    public function test_can_filter_rankings_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        // user1 のランキング2件（新しいランキングシステム対応）
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user1->id, 
            'category_id' => $category->id,
            'is_public' => true
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user1->id, 
            'category_id' => $category->id,
            'is_public' => true
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1
        ]);

        // user2 のランキング1件
        $ranking3 = Ranking::factory()->create([
            'user_id' => $user2->id, 
            'category_id' => $category->id,
            'is_public' => true
        ]);
        $ranking3->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1
        ]);

        $response = $this->getJson("/api/rankings?user_id={$user1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // 全てのランキングがuser1のものであることを確認
        foreach ($data as $ranking) {
            $this->assertEquals($user1->id, $ranking['user']['id']);
        }
    }
}
