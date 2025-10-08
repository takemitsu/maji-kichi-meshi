<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations and seed categories
        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_can_list_all_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'type',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_it_can_show_single_category(): void
    {
        $category = Category::where('slug', 'ramen')->first();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => 'ramen',
                    'type' => $category->type,
                ],
            ]);
    }

    public function test_it_requires_authentication_to_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'New Category',
            'type' => 'basic',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_category(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => 'Test Category',
            'type' => 'basic',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Category',
                    'slug' => 'test-category',
                    'type' => 'basic',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    public function test_it_validates_category_creation_data(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => '', // Invalid: required
            'type' => 'invalid', // Invalid: not in allowed values
        ]);

        $response->assertStatus(422);

        // Check actual validation errors
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('type', $errors);
    }

    public function test_it_auto_generates_slug_when_not_provided(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/categories', [
            'name' => 'Auto Generated Slug',
            'type' => 'basic',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Auto Generated Slug',
                    'slug' => 'auto-generated-slug',
                ],
            ]);
    }

    public function test_authenticated_user_can_update_category(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'type' => 'basic',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'slug' => 'updated-name',
                ],
            ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);
    }

    public function test_it_prevents_deleting_category_in_use(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $category = Category::factory()->create([
            'name' => 'Category In Use',
            'slug' => 'category-in-use',
            'type' => 'basic',
        ]);

        // Create a shop that uses this category
        $shop = \App\Models\Shop::factory()->create();
        $shop->categories()->attach($category->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Cannot delete category that is in use',
            ]);

        // Verify category still exists
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_it_can_delete_unused_category(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $category = Category::factory()->create([
            'name' => 'Unused Category',
            'type' => 'basic',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Category deleted successfully']);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // =============================================================================
    // Additional Coverage Tests
    // =============================================================================

    public function test_it_requires_authentication_to_update_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'type' => 'basic',
        ]);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_validates_category_update_data(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'type' => 'basic',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/categories/{$category->id}", [
            'name' => '', // Invalid: required
            'type' => 'invalid', // Invalid: not in allowed values
        ]);

        $response->assertStatus(422);

        // Check actual validation errors
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_it_requires_authentication_to_delete_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'type' => 'basic',
        ]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(401);
    }
}
