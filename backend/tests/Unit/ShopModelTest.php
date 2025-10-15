<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ShopImage;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_it_has_fillable_attributes(): void
    {
        $shop = Shop::create([
            'name' => 'Test Shop',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 35.7040,
            'longitude' => 139.5577,
            'phone' => '0422-22-1234',
            'website' => 'https://test.example.com',
            'google_place_id' => 'test_place_id',
            'is_closed' => false,
            'status' => 'active',
        ]);

        $this->assertEquals('Test Shop', $shop->name);
        $this->assertEquals('Test Description', $shop->description);
        $this->assertEquals('Test Address', $shop->address);
        $this->assertEquals(35.7040, $shop->latitude);
        $this->assertEquals(139.5577, $shop->longitude);
        $this->assertEquals('0422-22-1234', $shop->phone);
        $this->assertEquals('https://test.example.com', $shop->website);
        $this->assertEquals('test_place_id', $shop->google_place_id);
        $this->assertFalse($shop->is_closed);
        $this->assertEquals('active', $shop->status);
    }

    public function test_it_has_categories_relationship(): void
    {
        $shop = Shop::factory()->create();
        $category = Category::factory()->create();

        $shop->categories()->attach($category->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $shop->categories);
        $this->assertEquals(1, $shop->categories->count());
        $this->assertTrue($shop->categories->contains($category));
    }

    public function test_it_has_reviews_relationship(): void
    {
        $shop = Shop::factory()->create();
        $review = Review::factory()->create(['shop_id' => $shop->id]);

        $shop->load('reviews');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $shop->reviews);
        $this->assertEquals(1, $shop->reviews->count());
        $this->assertTrue($shop->reviews->contains($review));
    }

    public function test_it_has_images_relationship(): void
    {
        $shop = Shop::factory()->create();
        $image = ShopImage::factory()->create([
            'shop_id' => $shop->id,
            'moderation_status' => 'published',
        ]);

        $shop->load('images');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $shop->images);
        $this->assertEquals(1, $shop->images->count());
        $this->assertTrue($shop->images->contains($image));
    }

    public function test_it_has_wishlists_relationship(): void
    {
        $shop = Shop::factory()->create();
        $user = User::factory()->create();
        // Create wishlist with all required fields
        $wishlist = Wishlist::create([
            'shop_id' => $shop->id,
            'user_id' => $user->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $shop->load('wishlists');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $shop->wishlists);
        $this->assertEquals(1, $shop->wishlists->count());
        $this->assertTrue($shop->wishlists->contains($wishlist));
    }

    public function test_open_scope_filters_non_closed_shops(): void
    {
        $openShop = Shop::factory()->create(['is_closed' => false]);
        $closedShop = Shop::factory()->create(['is_closed' => true]);

        $openShops = Shop::open()->get();

        $this->assertTrue($openShops->contains($openShop));
        $this->assertFalse($openShops->contains($closedShop));
    }

    public function test_near_scope_finds_shops_within_radius(): void
    {
        // Kichijoji station coordinates (35.7040, 139.5577)
        $nearShop = Shop::factory()->create([
            'name' => 'Near Shop',
            'latitude' => 35.7050, // ~100m away
            'longitude' => 139.5580,
        ]);

        $farShop = Shop::factory()->create([
            'name' => 'Far Shop',
            'latitude' => 35.8000, // ~10km away
            'longitude' => 139.6000,
        ]);

        // Search within 5km radius
        $nearbyShops = Shop::near(35.7040, 139.5577, 5)->get();

        $this->assertTrue($nearbyShops->contains('id', $nearShop->id));
        $this->assertFalse($nearbyShops->contains('id', $farShop->id));
    }

    public function test_timestamps_are_present(): void
    {
        $shop = Shop::factory()->create();

        $this->assertNotNull($shop->created_at);
        $this->assertNotNull($shop->updated_at);
    }
}
