<?php

namespace Tests\Unit;

use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_it_has_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();

        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'status' => 'want_to_go',
            'priority' => 2,
            'source_type' => 'shop_detail',
            'memo' => 'Test memo',
        ]);

        $this->assertEquals($user->id, $wishlist->user_id);
        $this->assertEquals($shop->id, $wishlist->shop_id);
        $this->assertEquals('want_to_go', $wishlist->status);
        $this->assertEquals(2, $wishlist->priority);
        $this->assertEquals('shop_detail', $wishlist->source_type);
        $this->assertEquals('Test memo', $wishlist->memo);
    }

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $this->assertInstanceOf(User::class, $wishlist->user);
        $this->assertEquals($user->id, $wishlist->user->id);
    }

    public function test_it_belongs_to_shop(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $this->assertInstanceOf(Shop::class, $wishlist->shop);
        $this->assertEquals($shop->id, $wishlist->shop->id);
    }

    public function test_priority_label_attribute_returns_correct_labels(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();
        $shop3 = Shop::factory()->create();

        $wishlist1 = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'source_type' => 'shop_detail',
            'priority' => 1,
        ]);

        $wishlist2 = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $wishlist3 = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop3->id,
            'source_type' => 'shop_detail',
            'priority' => 3,
        ]);

        $this->assertEquals('いつか', $wishlist1->priority_label);
        $this->assertEquals('そのうち', $wishlist2->priority_label);
        $this->assertEquals('絶対', $wishlist3->priority_label);
    }

    public function test_timestamps_are_present(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $this->assertNotNull($wishlist->created_at);
        $this->assertNotNull($wishlist->updated_at);
    }

    public function test_visited_at_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
            'visited_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $wishlist->visited_at);
    }

    public function test_priority_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $wishlist = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'source_type' => 'shop_detail',
            'priority' => '2', // String
        ]);

        $this->assertIsInt($wishlist->priority);
        $this->assertEquals(2, $wishlist->priority);
    }

    public function test_source_type_enum_values(): void
    {
        $user = User::factory()->create();
        $shop1 = Shop::factory()->create();
        $shop2 = Shop::factory()->create();

        $wishlist1 = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop1->id,
            'source_type' => 'review',
            'priority' => 2,
        ]);

        $wishlist2 = Wishlist::create([
            'user_id' => $user->id,
            'shop_id' => $shop2->id,
            'source_type' => 'shop_detail',
            'priority' => 2,
        ]);

        $this->assertEquals('review', $wishlist1->source_type);
        $this->assertEquals('shop_detail', $wishlist2->source_type);
    }
}
