<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingModelTest extends TestCase
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
        $category = Category::first();

        $ranking = Ranking::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Ranking',
            'description' => 'Test Description',
            'is_public' => true,
        ]);

        $this->assertEquals($user->id, $ranking->user_id);
        $this->assertEquals($category->id, $ranking->category_id);
        $this->assertEquals('Test Ranking', $ranking->title);
        $this->assertEquals('Test Description', $ranking->description);
        $this->assertTrue($ranking->is_public);
    }

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $ranking = Ranking::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $ranking->user);
        $this->assertEquals($user->id, $ranking->user->id);
    }

    public function test_it_belongs_to_category(): void
    {
        $category = Category::first();
        $ranking = Ranking::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $ranking->category);
        $this->assertEquals($category->id, $ranking->category->id);
    }

    public function test_it_has_many_items(): void
    {
        $ranking = Ranking::factory()->create();
        $item = RankingItem::factory()->create([
            'ranking_id' => $ranking->id,
            'rank_position' => 1,
        ]);

        $ranking->load('items');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $ranking->items);
        $this->assertEquals(1, $ranking->items->count());
        $this->assertTrue($ranking->items->contains($item));
    }

    public function test_public_scope_filters_public_rankings(): void
    {
        $publicRanking = Ranking::factory()->create(['is_public' => true]);
        $privateRanking = Ranking::factory()->create(['is_public' => false]);

        $publicRankings = Ranking::public()->get();

        $this->assertTrue($publicRankings->contains($publicRanking));
        $this->assertFalse($publicRankings->contains($privateRanking));
    }

    public function test_by_user_scope_filters_by_user_id(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $ranking1 = Ranking::factory()->create(['user_id' => $user1->id]);
        $ranking2 = Ranking::factory()->create(['user_id' => $user2->id]);

        $user1Rankings = Ranking::byUser($user1->id)->get();

        $this->assertTrue($user1Rankings->contains($ranking1));
        $this->assertFalse($user1Rankings->contains($ranking2));
    }

    public function test_by_category_scope_filters_by_category_id(): void
    {
        $categories = Category::take(2)->get();
        $category1 = $categories->first();
        $category2 = $categories->last();

        $ranking1 = Ranking::factory()->create(['category_id' => $category1->id]);
        $ranking2 = Ranking::factory()->create(['category_id' => $category2->id]);

        $category1Rankings = Ranking::byCategory($category1->id)->get();

        $this->assertTrue($category1Rankings->contains($ranking1));
        $this->assertFalse($category1Rankings->contains($ranking2));
    }

    public function test_timestamps_are_present(): void
    {
        $ranking = Ranking::factory()->create();

        $this->assertNotNull($ranking->created_at);
        $this->assertNotNull($ranking->updated_at);
    }

    public function test_is_public_cast_to_boolean(): void
    {
        $ranking = Ranking::factory()->create(['is_public' => 1]);

        $this->assertIsBool($ranking->is_public);
        $this->assertTrue($ranking->is_public);
    }
}
