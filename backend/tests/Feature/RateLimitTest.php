<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->shop = Shop::factory()->create(['status' => 'active']);
        $this->category = Category::factory()->create();

        // JWTトークンを生成
        $this->token = auth('api')->login($this->user);

        // 画像アップロード用のストレージをfakeに設定
        Storage::fake('public');
    }

    public function review_creation_is_rate_limited_to_5_per_hour()
    {
        // 5回まで成功（異なる店舗で）
        for ($i = 0; $i < 5; $i++) {
            $shop = Shop::factory()->create(['status' => 'active']);
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->postJson('/api/reviews', [
                'shop_id' => $shop->id,
                'rating' => 5,
                'repeat_intention' => 'yes',
                'memo' => 'Test review ' . ($i + 1),
                'visited_at' => now()->format('Y-m-d'),
            ]);

            $response->assertStatus(201);
        }

        // 6回目は制限にかかる
        $shop6 = Shop::factory()->create(['status' => 'active']);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->postJson('/api/reviews', [
            'shop_id' => $shop6->id,
            'rating' => 5,
            'repeat_intention' => 'yes',
            'memo' => 'Test review 6',
            'visited_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function image_upload_is_rate_limited_to_20_per_hour()
    {
        // 20回まで成功するため、複数のレビューを作成
        for ($i = 0; $i < 20; $i++) {
            // 各アップロードごとに新しいレビューを作成
            $shop = Shop::factory()->create(['status' => 'active']);
            $review = Review::factory()->create([
                'user_id' => $this->user->id,
                'shop_id' => $shop->id,
            ]);

            $file = UploadedFile::fake()->image('test' . ($i + 1) . '.jpg');

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->postJson("/api/reviews/{$review->id}/images", [
                'images' => [$file],
            ]);

            $response->assertStatus(201);
        }

        // 21回目は制限にかかる
        $shop = Shop::factory()->create(['status' => 'active']);
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'shop_id' => $shop->id,
        ]);

        $file = UploadedFile::fake()->image('test21.jpg');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/reviews/{$review->id}/images", [
            'images' => [$file],
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function shop_creation_is_rate_limited_to_10_per_hour()
    {
        // 10回まで成功
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->postJson('/api/shops', [
                'name' => 'Test Shop ' . ($i + 1),
                'description' => 'Test description',
                'address' => 'Test address',
                'latitude' => 35.7022,
                'longitude' => 139.7744,
            ]);

            $response->assertStatus(201);
        }

        // 11回目は制限にかかる
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->postJson('/api/shops', [
            'name' => 'Test Shop 11',
            'description' => 'Test description',
            'address' => 'Test address',
            'latitude' => 35.7022,
            'longitude' => 139.7744,
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function ranking_creation_is_rate_limited_to_10_per_hour()
    {
        // 10回まで成功
        for ($i = 0; $i < 10; $i++) {
            // 各ランキングに異なる店舗を使用
            $shop = Shop::factory()->create(['status' => 'active']);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->postJson('/api/rankings', [
                'title' => 'Test Ranking ' . ($i + 1),
                'description' => 'Test description',
                'category_id' => $this->category->id,
                'is_public' => true,
                'shops' => [
                    [
                        'shop_id' => $shop->id,
                        'position' => 1,
                    ],
                ],
            ]);

            $response->assertStatus(201);
        }

        // 11回目は制限にかかる
        $shop = Shop::factory()->create(['status' => 'active']);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->postJson('/api/rankings', [
            'title' => 'Test Ranking 11',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'is_public' => true,
            'shops' => [
                [
                    'shop_id' => $shop->id,
                    'position' => 1,
                ],
            ],
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    public function test_rate_limit_is_per_user_not_per_ip()
    {
        // 別のユーザーを作成
        $user2 = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);
        $token2 = auth('api')->login($user2);

        // ユーザー1で5回レビュー作成（異なる店舗で）
        for ($i = 0; $i < 5; $i++) {
            $shop = Shop::factory()->create(['status' => 'active']);
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->postJson('/api/reviews', [
                'shop_id' => $shop->id,
                'rating' => 5,
                'repeat_intention' => 'yes',
                'memo' => 'User1 review ' . ($i + 1),
                'visited_at' => now()->format('Y-m-d'),
            ]);

            $response->assertStatus(201);
        }

        // レート制限キャッシュをクリアしてユーザー2のテストを開始
        \Illuminate\Support\Facades\Cache::flush();

        // ユーザー2は同じIPからでも5回作成できる（異なる店舗で）
        for ($i = 0; $i < 5; $i++) {
            $shop = Shop::factory()->create(['status' => 'active']);
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token2,
                'Content-Type' => 'application/json',
            ])->postJson('/api/reviews', [
                'shop_id' => $shop->id,
                'rating' => 4,
                'repeat_intention' => 'yes',
                'memo' => 'User2 review ' . ($i + 1),
                'visited_at' => now()->format('Y-m-d'),
            ]);

            $response->assertStatus(201);
        }

        // ユーザー1は制限にかかる
        $shop = Shop::factory()->create(['status' => 'active']);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 5,
            'repeat_intention' => 'yes',
            'memo' => 'User1 review 6',
            'visited_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(429);

        // ユーザー2も制限にかかる
        $shop = Shop::factory()->create(['status' => 'active']);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Content-Type' => 'application/json',
        ])->postJson('/api/reviews', [
            'shop_id' => $shop->id,
            'rating' => 4,
            'repeat_intention' => 'yes',
            'memo' => 'User2 review 6',
            'visited_at' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(429);
    }

    public function test_read_operations_have_higher_rate_limits()
    {
        // 読み取り操作（my-reviews）は100回/時間まで可能
        for ($i = 0; $i < 50; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson('/api/my-reviews');

            $response->assertStatus(200);
        }

        // まだ制限にかからない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/my-reviews');

        $response->assertStatus(200);
    }
}
