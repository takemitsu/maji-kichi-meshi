<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $reviewLike = new ReviewLike;

        $expected = [
            'user_id',
            'review_id',
        ];

        $this->assertEquals($expected, $reviewLike->getFillable());
    }

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $reviewLike->user());
        $this->assertInstanceOf(User::class, $reviewLike->user);
        $this->assertEquals($user->id, $reviewLike->user->id);
        $this->assertEquals('Test User', $reviewLike->user->name);
    }

    public function test_it_belongs_to_review(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'rating' => 5,
        ]);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $reviewLike->review());
        $this->assertInstanceOf(Review::class, $reviewLike->review);
        $this->assertEquals($review->id, $reviewLike->review->id);
        $this->assertEquals(5, $reviewLike->review->rating);
    }

    public function test_it_has_only_created_at_timestamp(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // created_at exists
        $this->assertNotNull($reviewLike->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $reviewLike->created_at);

        // updated_at is null (UPDATED_AT = null in model)
        $this->assertNull($reviewLike->updated_at);

        // Check database directly
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_it_casts_created_at_to_datetime(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $reviewLike->created_at);
        $this->assertTrue($reviewLike->created_at->isToday());
    }

    public function test_it_can_be_created_and_deleted(): void
    {
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
        ]);

        $reviewLike = ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $reviewLike->delete();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_updated_at_constant_is_null(): void
    {
        $this->assertNull(ReviewLike::UPDATED_AT);
    }
}
