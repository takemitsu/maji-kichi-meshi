<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_has_fillable_attributes(): void
    {
        $category = new Category;

        $expected = [
            'name',
            'slug',
            'type',
        ];

        $this->assertEquals($expected, $category->getFillable());
    }

    public function test_it_has_many_shops_relationship(): void
    {
        $category = Category::where('slug', 'ramen')->first();
        $shop1 = Shop::factory()->create(['name' => 'Ramen Shop 1']);
        $shop2 = Shop::factory()->create(['name' => 'Ramen Shop 2']);

        $category->shops()->attach([$shop1->id, $shop2->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $category->shops());
        $this->assertCount(2, $category->shops);
        $this->assertTrue($category->shops->contains($shop1));
        $this->assertTrue($category->shops->contains($shop2));
    }

    public function test_it_has_many_rankings_relationship(): void
    {
        $category = Category::where('slug', 'ramen')->first();
        $user = User::factory()->create();

        $ranking1 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Best Ramen 2024',
        ]);
        $ranking2 = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Top Ramen Shops',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->rankings());
        $this->assertCount(2, $category->rankings);
        $this->assertTrue($category->rankings->contains($ranking1));
        $this->assertTrue($category->rankings->contains($ranking2));
    }

    public function test_it_can_scope_by_slug(): void
    {
        $ramenCategory = Category::bySlug('ramen')->first();

        $this->assertNotNull($ramenCategory);
        $this->assertEquals('ramen', $ramenCategory->slug);
        $this->assertEquals('ラーメン', $ramenCategory->name);
    }

    public function test_scope_by_slug_returns_null_for_nonexistent_slug(): void
    {
        $category = Category::bySlug('nonexistent-category')->first();

        $this->assertNull($category);
    }

    public function test_it_has_timestamps(): void
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'type' => 'basic',
        ]);

        $this->assertNotNull($category->created_at);
        $this->assertNotNull($category->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $category->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $category->updated_at);
    }

    public function test_it_can_be_created_with_all_attributes(): void
    {
        $category = Category::create([
            'name' => 'Italian',
            'slug' => 'italian',
            'type' => 'basic',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Italian',
            'slug' => 'italian',
            'type' => 'basic',
        ]);
    }

    public function test_shops_many_to_many_pivot_table(): void
    {
        $category = Category::where('slug', 'ramen')->first();
        $shop = Shop::factory()->create(['name' => 'Test Ramen Shop']);

        $category->shops()->attach($shop->id);

        $this->assertDatabaseHas('shop_categories', [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
        ]);

        // Detach works
        $category->shops()->detach($shop->id);

        $this->assertDatabaseMissing('shop_categories', [
            'shop_id' => $shop->id,
            'category_id' => $category->id,
        ]);
    }
}
