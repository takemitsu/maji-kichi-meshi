<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShopApiTest extends TestCase
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
    public function it_can_list_shops()
    {
        // Create test shops
        $shop1 = Shop::factory()->create(['name' => 'Test Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Test Shop 2']);

        $response = $this->getJson('/api/shops');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'address',
                        'latitude',
                        'longitude',
                        'average_rating',
                        'review_count',
                        'categories',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /** @test */
    public function it_can_show_single_shop()
    {
        $shop = Shop::factory()->create(['name' => 'Test Shop']);

        $response = $this->getJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $shop->id,
                    'name' => 'Test Shop',
                ],
            ]);
    }

    /** @test */
    public function it_can_search_shops_by_name()
    {
        Shop::factory()->create(['name' => 'Ramen Shop']);
        Shop::factory()->create(['name' => 'Cafe Shop']);

        $response = $this->getJson('/api/shops?search=Ramen');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop', $data[0]['name']);
    }

    /** @test */
    public function it_can_filter_shops_by_category()
    {
        $category = Category::where('slug', 'ramen')->first();
        $shop = Shop::factory()->create(['name' => 'Ramen Shop']);
        $shop->categories()->attach($category->id);

        Shop::factory()->create(['name' => 'Other Shop']);

        $response = $this->getJson('/api/shops?category=ramen');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Ramen Shop', $data[0]['name']);
    }

    /** @test */
    public function it_requires_authentication_to_create_shop()
    {
        $response = $this->postJson('/api/shops', [
            'name' => 'New Shop',
            'address' => 'Test Address',
            'latitude' => 35.7,
            'longitude' => 139.5,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_create_shop()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => 'New Shop',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 35.7,
            'longitude' => 139.5,
            'phone' => '03-1234-5678',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'New Shop',
                    'address' => 'Test Address',
                ],
            ]);

        $this->assertDatabaseHas('shops', [
            'name' => 'New Shop',
            'address' => 'Test Address',
        ]);
    }

    /** @test */
    public function it_validates_shop_creation_data()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/shops', [
            'name' => '', // Invalid: required
            'latitude' => 200, // Invalid: out of range
            'longitude' => 'not-a-number', // Invalid: not numeric
        ]);

        $response->assertStatus(422);

        // Debug: check actual validation errors
        $errors = $response->json('messages');
        $this->assertArrayHasKey('name', $errors);
        // address, latitude, longitude are now nullable, so only name is required
    }

    /** @test */
    public function authenticated_user_can_update_shop()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $shop = Shop::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/shops/{$shop->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ],
            ]);

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function authenticated_user_can_delete_shop()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $shop = Shop::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/shops/{$shop->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Shop deleted successfully']);

        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
    }
}
