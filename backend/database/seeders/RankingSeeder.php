<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ranking;
use App\Models\RankingItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class RankingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 一般ユーザーを取得（ReviewSeederで作成されたユーザー）
        $users = User::where('role', 'user')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No regular users found. Creating sample users.');
            $users = User::factory()->count(5)->create();
        }

        $shops = Shop::all();
        $categories = Category::where('type', 'ranking')->get();

        $totalRankings = 0;

        // 各ユーザーに対してランキングを作成
        foreach ($users->take(8) as $userIndex => $user) {
            // 各ユーザーごとに異なるカテゴリでランキングを作成
            $userCategories = $categories->shuffle()->take(3);

            foreach ($userCategories as $categoryIndex => $category) {
                $categoryShops = $shops->filter(function ($shop) use ($category) {
                    return $shop->categories->contains($category);
                });

                if ($categoryShops->isNotEmpty()) {
                    $uniqueTitle = $category->name . 'ランキング_' . $user->id . '_' . $categoryIndex;
                    $this->createRankingForUser($user, $category, $categoryShops, $uniqueTitle);
                    $totalRankings++;
                }
            }
        }

        // 21件以上になるよう追加ランキングを作成
        $additionalRankingsNeeded = max(0, 22 - $totalRankings);

        for ($i = 0; $i < $additionalRankingsNeeded; $i++) {
            $user = $users->random();
            $category = $categories->random();
            $rankingTitles = [
                'おすすめランキング',
                'お気に入りランキング',
                'リピートランキング',
                'コスパランキング',
                '味ランキング',
                '雰囲気ランキング',
                'デートランキング',
                'ファミリーランキング',
            ];
            $rankingTitle = $rankingTitles[array_rand($rankingTitles)];

            // 重複チェック（同じユーザー・タイトル・カテゴリの組み合わせを避ける）
            $finalTitle = $rankingTitle . ' #' . ($i + 1) . '_' . $user->id;

            $this->createRankingForUser($user, $category, $shops, $finalTitle);
            $totalRankings++;
        }

        $this->command->info("Sample rankings created successfully. Total: {$totalRankings} rankings.");
    }

    private function createRankingForUser($user, $category, $shops, $title): void
    {
        // ランダムに3-5店舗を選択
        $selectedShops = $shops->random(min(rand(3, 5), $shops->count()));

        // ランキングレコードを作成
        $ranking = Ranking::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => $this->generateRankingDescription($title),
            'is_public' => rand(0, 2) >= 1, // 約70%の確率で公開
        ]);

        // ランキングアイテムを作成
        foreach ($selectedShops as $index => $shop) {
            RankingItem::create([
                'ranking_id' => $ranking->id,
                'shop_id' => $shop->id,
                'rank_position' => $index + 1,
            ]);
        }
    }

    private function generateRankingDescription($title): string
    {
        $descriptions = [
            '個人的な好みを反映した' . $title . 'です。',
            '実際に食べ歩いて作成した' . $title . 'です。',
            '主観的ですが参考になればと思います。',
            'コスパや味を総合的に判断しました。',
            'また食べに行きたい順で並べました。',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
