<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
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

        // 他のユーザーのデータも作成（除外されることを確認）
        $otherUser = User::factory()->create();
        Review::factory()->count(2)->create(['user_id' => $otherUser->id, 'shop_id' => $shop->id]);
        Ranking::factory()->count(1)->create(['user_id' => $otherUser->id, 'category_id' => $category->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/stats/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'reviews_count' => 5,
                    'rankings_count' => 3,
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
                ],
            ]);
    }
}
