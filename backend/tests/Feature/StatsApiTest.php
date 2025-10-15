<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_returns_correct_counts(): void
    {
        // ユーザーと関連データを作成
        $user = User::factory()->create();
        $shop = Shop::factory()->create();
        $category = Category::factory()->create();

        // レビューとランキングを作成
        Review::factory()->count(5)->create(['user_id' => $user->id, 'shop_id' => $shop->id]);
        Ranking::factory()->count(3)->create(['user_id' => $user->id, 'category_id' => $category->id]);

        // いいねと行きたいリストを作成
        $otherReviews = Review::factory()->count(4)->create(['shop_id' => $shop->id]);
        foreach ($otherReviews as $review) {
            ReviewLike::factory()->create(['user_id' => $user->id, 'review_id' => $review->id]);
        }

        $wishlistShops = Shop::factory()->count(7)->create();
        foreach ($wishlistShops as $wishlistShop) {
            Wishlist::factory()->wantToGo()->create([
                'user_id' => $user->id,
                'shop_id' => $wishlistShop->id,
            ]);
        }

        // 「行った」ステータスのウィッシュリストも作成（カウントされないことを確認）
        $visitedShops = Shop::factory()->count(2)->create();
        foreach ($visitedShops as $visitedShop) {
            Wishlist::factory()->visited()->create([
                'user_id' => $user->id,
                'shop_id' => $visitedShop->id,
            ]);
        }

        // 他のユーザーのデータも作成（除外されることを確認）
        $otherUser = User::factory()->create();
        Review::factory()->count(2)->create(['user_id' => $otherUser->id, 'shop_id' => $shop->id]);
        Ranking::factory()->count(1)->create(['user_id' => $otherUser->id, 'category_id' => $category->id]);
        foreach ($otherReviews->take(3) as $otherReview) {
            ReviewLike::factory()->create(['user_id' => $otherUser->id, 'review_id' => $otherReview->id]);
        }
        $otherUserShops = Shop::factory()->count(5)->create();
        foreach ($otherUserShops as $otherUserShop) {
            Wishlist::factory()->wantToGo()->create(['user_id' => $otherUser->id, 'shop_id' => $otherUserShop->id]);
        }

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/stats/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'reviews_count' => 5,
                    'rankings_count' => 3,
                    'liked_reviews_count' => 4,
                    'wishlists_count' => 7,
                ],
            ]);
    }

    public function test_dashboard_stats_requires_authentication(): void
    {
        $response = $this->getJson('/api/stats/dashboard');

        $response->assertStatus(401);
    }

    public function test_dashboard_stats_returns_zero_counts_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/stats/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'reviews_count' => 0,
                    'rankings_count' => 0,
                    'liked_reviews_count' => 0,
                    'wishlists_count' => 0,
                ],
            ]);
    }
}
