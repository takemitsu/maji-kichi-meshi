<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingItemModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);
    }

    public function test_it_has_fillable_attributes(): void
    {
        $rankingItem = new RankingItem;

        $expected = [
            'ranking_id',
            'shop_id',
            'rank_position',
            'comment',
        ];

        $this->assertEquals($expected, $rankingItem->getFillable());
    }

    public function test_it_belongs_to_ranking(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $shop = Shop::factory()->create();

        $rankingItem = RankingItem::create([
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $rankingItem->ranking());
        $this->assertInstanceOf(Ranking::class, $rankingItem->ranking);
        $this->assertEquals($ranking->id, $rankingItem->ranking->id);
    }

    public function test_it_belongs_to_shop(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $shop = Shop::factory()->create(['name' => 'Test Shop']);

        $rankingItem = RankingItem::create([
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $rankingItem->shop());
        $this->assertInstanceOf(Shop::class, $rankingItem->shop);
        $this->assertEquals($shop->id, $rankingItem->shop->id);
        $this->assertEquals('Test Shop', $rankingItem->shop->name);
    }

    public function test_it_can_create_ranking_item_with_comment(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $shop = Shop::factory()->create();

        $rankingItem = RankingItem::create([
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 1,
            'comment' => 'This is my favorite!',
        ]);

        $this->assertEquals('This is my favorite!', $rankingItem->comment);
        $this->assertDatabaseHas('ranking_items', [
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 1,
            'comment' => 'This is my favorite!',
        ]);
    }

    public function test_it_can_create_ranking_item_without_comment(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $shop = Shop::factory()->create();

        $rankingItem = RankingItem::create([
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 2,
        ]);

        $this->assertNull($rankingItem->comment);
        $this->assertDatabaseHas('ranking_items', [
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 2,
            'comment' => null,
        ]);
    }

    public function test_it_has_timestamps(): void
    {
        $user = User::factory()->create();
        $category = Category::first();
        $ranking = Ranking::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $shop = Shop::factory()->create();

        $rankingItem = RankingItem::create([
            'ranking_id' => $ranking->id,
            'shop_id' => $shop->id,
            'rank_position' => 1,
        ]);

        $this->assertNotNull($rankingItem->created_at);
        $this->assertNotNull($rankingItem->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $rankingItem->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $rankingItem->updated_at);
    }
}
