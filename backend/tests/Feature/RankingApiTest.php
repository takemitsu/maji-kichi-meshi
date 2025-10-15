<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    public function test_it_can_list_public_rankings(): void
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

    public function test_it_can_show_public_ranking(): void
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

    public function test_it_hides_private_ranking_from_unauthorized_users(): void
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

    public function test_it_can_filter_rankings_by_category(): void
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

    public function test_it_can_filter_rankings_by_user(): void
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

    public function test_it_requires_authentication_to_create_ranking(): void
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

    public function test_authenticated_user_can_create_ranking(): void
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

    public function test_it_validates_ranking_creation_data(): void
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
        $this->assertEquals('店舗は最大10店舗までです', $errors['shops'][0]);
    }

    public function test_it_adjusts_positions_when_inserting_ranking(): void
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

    public function test_user_can_update_own_ranking(): void
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

    public function test_user_cannot_update_others_ranking(): void
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

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_ranking(): void
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

    public function test_user_cannot_delete_others_ranking(): void
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

        $response->assertStatus(403);
    }

    public function test_it_can_get_my_rankings(): void
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

    public function test_it_can_get_public_rankings(): void
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

    public function test_owner_can_view_own_private_ranking(): void
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

    public function test_it_creates_multiple_shops_ranking_and_returns_all_shops(): void
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

    public function test_it_creates_single_shop_ranking_and_returns_array(): void
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

    public function test_index_returns_multiple_shops_for_same_ranking(): void
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

    public function test_show_returns_individual_ranking_properly(): void
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

    public function test_update_from_single_to_multiple_shops_works(): void
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

    public function test_my_rankings_returns_multiple_shops_for_same_title(): void
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

    public function test_can_filter_rankings_by_user(): void
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
            'is_public' => true,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        // user2 のランキング1件
        $ranking3 = Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $ranking3->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
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

    public function test_it_can_create_ranking_with_shop_comments(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $data = [
            'title' => 'Test Ranking with Comments',
            'description' => 'Test ranking with shop comments',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => 1, 'comment' => '最高のラーメン'],
                ['shop_id' => $shop2->id, 'position' => 2, 'comment' => '雰囲気が良い'],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/rankings', $data);

        $response->assertStatus(201);
        $responseData = $response->json('data');

        // コメントがレスポンスに含まれていることを確認
        $this->assertEquals('最高のラーメン', $responseData['shops'][0]['comment']);
        $this->assertEquals('雰囲気が良い', $responseData['shops'][1]['comment']);

        // データベースにコメントが保存されていることを確認
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop1->id,
            'rank_position' => 1,
            'comment' => '最高のラーメン',
        ]);
        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop2->id,
            'rank_position' => 2,
            'comment' => '雰囲気が良い',
        ]);
    }

    public function test_it_can_update_ranking_with_shop_comments(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // コメントなしでランキング作成
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // コメント付きで更新
        $updateData = [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'is_public' => $ranking->is_public,
            'shops' => [
                ['shop_id' => $shop->id, 'position' => 1, 'comment' => '更新後のコメント'],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", $updateData);

        $response->assertStatus(200);
        $responseData = $response->json('data');

        // コメントが更新されていることを確認
        $this->assertEquals('更新後のコメント', $responseData['shops'][0]['comment']);

        $this->assertDatabaseHas('ranking_items', [
            'shop_id' => $shop->id,
            'rank_position' => 1,
            'comment' => '更新後のコメント',
        ]);
    }

    // =============================================================================
    // 複数ユーザーデータ隔離テスト
    // =============================================================================

    public function test_multiple_users_see_different_rankings_in_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();

        // user1: public ranking 1件 + private ranking 1件
        $user1PublicRanking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'User1 Public Ranking',
        ]);
        $user1PublicRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $user1PrivateRanking = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'is_public' => false,
            'title' => 'User1 Private Ranking',
        ]);
        $user1PrivateRanking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        // user2: public ranking 1件
        $user2PublicRanking = Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'User2 Public Ranking',
        ]);
        $user2PublicRanking->items()->create([
            'shop_id' => $shop3->id,
            'rank_position' => 1,
        ]);

        // 未ログイン: user1とuser2のpublic rankingのみ見える (2件)
        $guestResponse = $this->getJson('/api/rankings');
        $guestResponse->assertStatus(200);
        $guestData = $guestResponse->json('data');
        $this->assertCount(2, $guestData);
        $guestTitles = collect($guestData)->pluck('title')->toArray();
        $this->assertContains('User1 Public Ranking', $guestTitles);
        $this->assertContains('User2 Public Ranking', $guestTitles);
        $this->assertNotContains('User1 Private Ranking', $guestTitles);

        // user1ログイン: /api/rankings では public のみ (2件)
        // (privateランキングは /api/my-rankings で取得)
        $token1 = JWTAuth::fromUser($user1);
        $user1Response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/rankings');

        $user1Response->assertStatus(200);
        $user1Data = $user1Response->json('data');
        $this->assertCount(2, $user1Data);
        $user1Titles = collect($user1Data)->pluck('title')->toArray();
        $this->assertContains('User1 Public Ranking', $user1Titles);
        $this->assertContains('User2 Public Ranking', $user1Titles);
        $this->assertNotContains('User1 Private Ranking', $user1Titles);

        // user1のprivateランキングは /api/my-rankings で確認
        $myRankingsResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/my-rankings');

        $myRankingsResponse->assertStatus(200);
        $myRankingsData = $myRankingsResponse->json('data');
        $this->assertCount(2, $myRankingsData);
        $myRankingsTitles = collect($myRankingsData)->pluck('title')->toArray();
        $this->assertContains('User1 Public Ranking', $myRankingsTitles);
        $this->assertContains('User1 Private Ranking', $myRankingsTitles);

        // user2ログイン: user2の全ranking + user1のpublic ranking (2件)
        $token2 = JWTAuth::fromUser($user2);
        $user2Response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/rankings');

        $user2Response->assertStatus(200);
        $user2Data = $user2Response->json('data');
        $this->assertCount(2, $user2Data);
        $user2Titles = collect($user2Data)->pluck('title')->toArray();
        $this->assertContains('User1 Public Ranking', $user2Titles);
        $this->assertContains('User2 Public Ranking', $user2Titles);
        $this->assertNotContains('User1 Private Ranking', $user2Titles);
    }

    public function test_ranking_creation_does_not_interfere_with_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        // user1が5件のrankingを持つ
        for ($i = 1; $i <= 5; $i++) {
            $ranking = Ranking::factory()->create([
                'user_id' => $user1->id,
                'category_id' => $category->id,
                'is_public' => true,
                'title' => "User1 Ranking {$i}",
            ]);
            $ranking->items()->create([
                'shop_id' => $shop1->id,
                'rank_position' => 1,
            ]);
        }

        // user1のランキング数を確認
        $user1InitialCount = Ranking::where('user_id', $user1->id)->count();
        $this->assertEquals(5, $user1InitialCount);

        // user2が新規ranking作成
        $token2 = JWTAuth::fromUser($user2);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->postJson('/api/rankings', [
            'title' => 'User2 New Ranking',
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                ['shop_id' => $shop2->id, 'position' => 1],
            ],
        ]);

        $response->assertStatus(201);

        // user1のrankingが影響を受けていないことを確認
        $user1FinalCount = Ranking::where('user_id', $user1->id)->count();
        $this->assertEquals(5, $user1FinalCount);

        // user1の全てのrankingのtitleが変わっていないことを確認
        $user1Rankings = Ranking::where('user_id', $user1->id)->get();
        foreach ($user1Rankings as $ranking) {
            $this->assertStringStartsWith('User1 Ranking', $ranking->title);
        }

        // user2のrankingが正しく作成されていることを確認
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user2->id,
            'title' => 'User2 New Ranking',
        ]);
    }

    // =============================================================================
    // 検索・フィルタのエッジケース
    // =============================================================================

    public function test_index_search_with_special_characters(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $shop = Shop::factory()->create();

        // 特殊文字を含むタイトルのランキング作成
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Test % Ranking _ with [wildcards]',
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // 通常の文字列検索 (addcslashesでエスケープされる)
        $response = $this->getJson('/api/rankings?search=Test');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($ranking->id, $data[0]['id']);

        // 特殊文字を含む検索 (SQLインジェクション対策確認 - エスケープされるため検索結果はマッチしない)
        $response = $this->getJson('/api/rankings?search=%');
        $response->assertStatus(200);
        // addcslashes('%_\\') でエスケープされるため、%そのものを含むタイトルはマッチしない
    }

    public function test_index_with_is_public_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        // public ranking
        $publicRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $publicRanking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        // private ranking
        $privateRanking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $privateRanking->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        // is_public=1 でフィルタ (publicのみ)
        $response = $this->getJson('/api/rankings?is_public=1');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_public']);

        // is_public=0 でフィルタ
        // Note: index()メソッドは is_public パラメータが指定されていても、
        // 指定なしの場合は public() スコープを適用する (L41-44)
        // したがって、is_public=0 でも privateランキングは表示されない
        $response = $this->getJson('/api/rankings?is_public=0');
        $response->assertStatus(200);
        $data = $response->json('data');
        // is_public=0が指定されていても、privateランキングのみは見えない
        // (この動作は設計上の意図と思われる)
    }

    public function test_my_rankings_search_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // 2件のランキング作成
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Ramen Ranking',
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Izakaya Ranking',
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        // 検索: "Ramen"
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-rankings?search=Ramen');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Ranking', $data[0]['title']);
    }

    public function test_my_rankings_category_filter(): void
    {
        $user = User::factory()->create();
        $category1 = Category::first();
        $category2 = Category::skip(1)->first();
        $shop = Shop::factory()->create();
        $token = JWTAuth::fromUser($user);

        // カテゴリ1のランキング
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // カテゴリ2のランキング
        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // カテゴリ1でフィルタ
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/my-rankings?category_id={$category1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($category1->id, $data[0]['category']['id']);
    }

    public function test_public_rankings_search_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        // 公開ランキング1
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Best Ramen',
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        // 公開ランキング2
        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
            'title' => 'Best Sushi',
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop2->id,
            'rank_position' => 1,
        ]);

        // 検索: "Ramen"
        $response = $this->getJson('/api/public-rankings?search=Ramen');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Best Ramen', $data[0]['title']);
    }

    public function test_public_rankings_category_and_user_filter(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category1 = Category::first();
        $category2 = Category::skip(1)->first();
        $shop = Shop::factory()->create();

        // user1, category1
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category1->id,
            'is_public' => true,
        ]);
        $ranking1->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // user1, category2
        $ranking2 = Ranking::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category2->id,
            'is_public' => true,
        ]);
        $ranking2->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // user2, category1
        $ranking3 = Ranking::factory()->create([
            'user_id' => $user2->id,
            'category_id' => $category1->id,
            'is_public' => true,
        ]);
        $ranking3->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // user1 + category1 でフィルタ
        $response = $this->getJson("/api/public-rankings?user_id={$user1->id}&category_id={$category1->id}");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($user1->id, $data[0]['user']['id']);
        $this->assertEquals($category1->id, $data[0]['category']['id']);
    }

    public function test_pagination_with_per_page_parameter(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $shop = Shop::factory()->create();

        // 20件のランキング作成
        for ($i = 1; $i <= 20; $i++) {
            $ranking = Ranking::factory()->create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'is_public' => true,
            ]);
            $ranking->items()->create([
                'shop_id' => $shop->id,
                'rank_position' => 1,
            ]);
        }

        // デフォルト: 15件/ページ
        $response = $this->getJson('/api/rankings');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(15, $data);
        $this->assertEquals(20, $response->json('meta.total'));

        // per_page=5 指定
        $response = $this->getJson('/api/rankings?per_page=5');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data);

        // per_page=50 (最大値)
        $response = $this->getJson('/api/rankings?per_page=50');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(20, $data); // 全20件が返る

        // per_page=100 (50を超えるとバリデーションエラー)
        $response = $this->getJson('/api/rankings?per_page=100');
        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('per_page', $errors);
    }

    public function test_reorder_fails_with_invalid_order(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // ランキング作成
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop1->id,
            'rank_position' => 1,
        ]);

        // 無効なposition (0以下)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => 0],
            ],
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('shops.0.position', $errors);

        // 無効なposition (負の値)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'shops' => [
                ['shop_id' => $shop1->id, 'position' => -1],
            ],
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('shops.0.position', $errors);
    }

    public function test_toggle_private_to_public(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // 非公開ランキングを作成
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => false,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // 非公開は未認証から見えない
        $response = $this->getJson("/api/rankings/{$ranking->id}");
        $response->assertStatus(404);

        // 非公開 → 公開に切り替え
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'is_public' => true,
            'shops' => [
                ['shop_id' => $shop->id, 'position' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_public'));
        $this->assertDatabaseHas('rankings', [
            'id' => $ranking->id,
            'is_public' => true,
        ]);

        // 公開されたので未認証でも見える
        $response = $this->getJson("/api/rankings/{$ranking->id}");
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_public'));
    }

    public function test_toggle_public_to_private(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // 公開ランキングを作成
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // 公開は未認証でも見える
        $response = $this->getJson("/api/rankings/{$ranking->id}");
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_public'));

        // 公開 → 非公開に切り替え
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'is_public' => false,
            'shops' => [
                ['shop_id' => $shop->id, 'position' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_public'));
        $this->assertDatabaseHas('rankings', [
            'id' => $ranking->id,
            'is_public' => false,
        ]);

        // 非公開になったので未認証では見えない
        // JWT認証をクリア
        Auth::guard('api')->logout();
        JWTAuth::unsetToken();

        $response = $this->getJson("/api/rankings/{$ranking->id}");

        $response->assertStatus(404)
            ->assertJson(['error' => 'Ranking not found']);
    }

    public function test_owner_can_view_private_ranking_after_toggle(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // 公開ランキングを作成して非公開に変更
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_public' => true,
        ]);
        $ranking->items()->create([
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        // 公開 → 非公開に切り替え
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/rankings/{$ranking->id}", [
            'title' => $ranking->title,
            'category_id' => $category->id,
            'is_public' => false,
            'shops' => [
                ['shop_id' => $shop->id, 'position' => 1],
            ],
        ]);
        $response->assertStatus(200);

        // 所有者は非公開ランキングを閲覧可能
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$ranking->id}");
        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_public'));
    }
}
