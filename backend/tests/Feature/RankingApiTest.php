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
            'title' => 'Test Ranking',
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
                    [
                        'is_public' => true,
                        'title' => 'My favorite shop',
                        'description' => 'This is my number one choice',
                    ],
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
    public function it_validates_max_10_shops_limit()
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
                    [
                        'rank_position' => 2,
                        'is_public' => true,
                        'title' => 'Updated title',
                    ],
                ],
                'message' => 'Ranking updated successfully',
            ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rank_position' => 2,
            'is_public' => true,
            'title' => 'Updated title',
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

    /** @test */
    public function it_creates_multiple_shops_ranking_and_returns_all_shops()
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
                    '*' => [
                        'id',
                        'rank_position',
                        'title',
                        'description',
                        'is_public',
                        'user' => ['id', 'name', 'email'],
                        'category' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);

        // Check that all shops are returned in correct order
        // Note: Each response item represents one ranking record, but shops array contains all shops in the ranking
        $this->assertEquals($shop1->id, $responseData[0]['shops'][0]['id']);
        $this->assertEquals(1, $responseData[0]['rank_position']);
        $this->assertEquals($shop2->id, $responseData[1]['shops'][0]['id']);
        $this->assertEquals(2, $responseData[1]['rank_position']);
        $this->assertEquals($shop3->id, $responseData[2]['shops'][0]['id']);
        $this->assertEquals(3, $responseData[2]['rank_position']);

        // Verify database records
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'title' => 'Multiple Shops Test Ranking',
        ]);
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
            'title' => 'Multiple Shops Test Ranking',
        ]);
        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop3->id,
            'category_id' => $category->id,
            'rank_position' => 3,
            'title' => 'Multiple Shops Test Ranking',
        ]);
    }

    /** @test */
    public function it_creates_single_shop_ranking_and_returns_array()
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
                    '*' => [
                        'id',
                        'rank_position',
                        'title',
                        'description',
                        'is_public',
                        'user' => ['id', 'name', 'email'],
                        'category' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals($shop->id, $responseData[0]['shops'][0]['id']);
        $this->assertEquals(1, $responseData[0]['rank_position']);
    }

    /** @test */
    public function index_returns_multiple_shops_for_same_ranking()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();

        // Create multiple rankings with same title and category
        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'title' => 'Test Multiple Index',
            'is_public' => true,
        ]);

        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
            'title' => 'Test Multiple Index',
            'is_public' => true,
        ]);

        $response = $this->getJson('/api/rankings');

        $response->assertStatus(200);
        $responseData = $response->json('data');

        // Should return one grouped ranking containing both shops
        $this->assertCount(1, $responseData);
        $groupedRanking = $responseData[0];
        $this->assertEquals('Test Multiple Index', $groupedRanking['title']);

        // Check that we have both shops in the shops array
        $this->assertCount(2, $groupedRanking['shops']);
        $shopIds = collect($groupedRanking['shops'])->pluck('id');
        $this->assertTrue($shopIds->contains($shop1->id));
        $this->assertTrue($shopIds->contains($shop2->id));
    }

    /** @test */
    public function show_returns_individual_ranking_properly()
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'title' => 'Test Show Single',
            'is_public' => false,
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

    /** @test */
    public function update_from_single_to_multiple_shops_works()
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
        $rankingId = $createResponse->json('data.0.id');

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
                    '*' => [
                        'id',
                        'rank_position',
                        'title',
                        'description',
                        'is_public',
                        'user' => ['id', 'name', 'email'],
                        'category' => ['id', 'name'],
                        'created_at',
                        'updated_at',
                    ],
                ],
                'message',
            ]);

        $responseData = $updateResponse->json('data');
        $this->assertCount(3, $responseData);

        // Check that all shops are returned in correct order
        $this->assertEquals($shop1->id, $responseData[0]['shops'][0]['id']);
        $this->assertEquals(1, $responseData[0]['rank_position']);
        $this->assertEquals($shop2->id, $responseData[1]['shops'][0]['id']);
        $this->assertEquals(2, $responseData[1]['rank_position']);
        $this->assertEquals($shop3->id, $responseData[2]['shops'][0]['id']);
        $this->assertEquals(3, $responseData[2]['rank_position']);

        // Verify old ranking was deleted and new ones created
        $this->assertDatabaseMissing('rankings', [
            'user_id' => $user->id,
            'title' => 'Update Test Ranking',
        ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'title' => 'Updated Multiple Shops Ranking',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
            'title' => 'Updated Multiple Shops Ranking',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('rankings', [
            'user_id' => $user->id,
            'shop_id' => $shop3->id,
            'category_id' => $category->id,
            'rank_position' => 3,
            'title' => 'Updated Multiple Shops Ranking',
            'is_public' => true,
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
        $newRankingId = $responseData[0]['id'];
        $showResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/rankings/{$newRankingId}");

        $showResponse->assertStatus(200);
        $this->assertEquals('Updated Multiple Shops Ranking', $showResponse->json('data.title'));
    }

    /** @test */
    public function my_rankings_returns_multiple_shops_for_same_title()
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $category = Category::first();
        $token = JWTAuth::fromUser($user);

        // Create multiple rankings with same title
        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'category_id' => $category->id,
            'rank_position' => 1,
            'title' => 'My Multiple Shops Ranking',
            'is_public' => false,
        ]);

        Ranking::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'category_id' => $category->id,
            'rank_position' => 2,
            'title' => 'My Multiple Shops Ranking',
            'is_public' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-rankings');

        $response->assertStatus(200);
        $responseData = $response->json('data');

        // Should return one grouped ranking containing both shops
        $myRankings = collect($responseData)->where('title', 'My Multiple Shops Ranking');
        $this->assertEquals(1, $myRankings->count());

        $groupedRanking = $myRankings->first();
        $this->assertCount(2, $groupedRanking['shops']);

        // Check that we have rankings for both shops
        $shopIds = collect($groupedRanking['shops'])->pluck('id');
        $this->assertTrue($shopIds->contains($shop1->id));
        $this->assertTrue($shopIds->contains($shop2->id));
    }

    /** @test */
    public function can_filter_rankings_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $category = Category::first();

        // user1 のランキング2件
        Ranking::factory(2)->create(['user_id' => $user1->id, 'category_id' => $category->id]);
        // user2 のランキング1件
        Ranking::factory(1)->create(['user_id' => $user2->id, 'category_id' => $category->id]);

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
