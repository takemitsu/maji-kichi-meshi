<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 吉祥寺の代表的な店舗を作成
        $shops = [
            [
                'name' => '吉祥寺 一蘭',
                'description' => '博多豚骨ラーメン専門店',
                'address' => '東京都武蔵野市吉祥寺本町1-4-18',
                'latitude' => 35.7022,
                'longitude' => 139.5792,
                'phone' => '0422-22-1234',
                'categories' => ['ラーメン', 'ランチ営業'],
            ],
            [
                'name' => 'スターバックス 吉祥寺店',
                'description' => 'コーヒーチェーン店',
                'address' => '東京都武蔵野市吉祥寺本町1-5-1',
                'latitude' => 35.7025,
                'longitude' => 139.5795,
                'phone' => '0422-22-5678',
                'categories' => ['カフェ・喫茶店', 'ランチ営業'],
            ],
            [
                'name' => 'やよい軒 吉祥寺店',
                'description' => '定食チェーン店',
                'address' => '東京都武蔵野市吉祥寺本町1-6-2',
                'latitude' => 35.7028,
                'longitude' => 139.5798,
                'phone' => '0422-22-9012',
                'categories' => ['定食・食堂', 'ランチ営業'],
            ],
            [
                'name' => 'マクドナルド 吉祥寺店',
                'description' => 'ファストフードチェーン店',
                'address' => '東京都武蔵野市吉祥寺本町1-7-3',
                'latitude' => 35.7031,
                'longitude' => 139.5801,
                'phone' => '0422-22-3456',
                'categories' => ['ファストフード', 'ランチ営業'],
            ],
            [
                'name' => '居酒屋 吉祥寺横丁',
                'description' => '昔ながらの居酒屋',
                'address' => '東京都武蔵野市吉祥寺本町1-8-4',
                'latitude' => 35.7034,
                'longitude' => 139.5804,
                'phone' => '0422-22-7890',
                'categories' => ['居酒屋・バー', '深夜営業'],
            ],
        ];

        foreach ($shops as $shopData) {
            $categoryNames = $shopData['categories'];
            unset($shopData['categories']);

            $shop = Shop::create($shopData);

            // カテゴリを関連付け
            $categories = Category::whereIn('name', $categoryNames)->get();
            $shop->categories()->attach($categories);
        }

        // 追加で21件以上になるようにファクトリで店舗を作成
        $additionalShopsNeeded = max(0, 22 - count($shops));

        if ($additionalShopsNeeded > 0) {
            $allCategories = Category::all();

            Shop::factory()
                ->count($additionalShopsNeeded)
                ->create()
                ->each(function ($shop) use ($allCategories) {
                    // ランダムに1-3個のカテゴリを関連付け
                    $randomCategories = $allCategories->random(rand(1, 3));
                    $shop->categories()->attach($randomCategories);
                });

            $this->command->info("Created {$additionalShopsNeeded} additional shops via factory.");
        }

        $this->command->info('Sample shops created successfully.');
    }
}
