<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 一般ユーザーを作成（レビュー投稿用）
        $users = User::factory()->count(10)->create();

        // 各店舗に対してレビューを作成
        $shops = Shop::all();

        foreach ($shops as $shop) {
            // 各店舗に1-5件のレビューを作成
            $reviewCount = rand(1, 5);

            for ($i = 0; $i < $reviewCount; $i++) {
                $user = $users->random();

                // 同じユーザーが同じ店舗に重複レビューしないようにチェック
                if (Review::where('user_id', $user->id)->where('shop_id', $shop->id)->exists()) {
                    continue;
                }

                Review::create([
                    'user_id' => $user->id,
                    'shop_id' => $shop->id,
                    'rating' => rand(1, 5),
                    'repeat_intention' => ['また行く', 'わからん', '行かない'][rand(0, 2)],
                    'memo' => $this->generateRandomComment(),
                    'visited_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('Sample reviews created successfully.');
    }

    private function generateRandomComment(): string
    {
        $comments = [
            '美味しかったです！また来たいと思います。',
            '接客も良く、料理も満足でした。',
            '値段の割にボリュームがあって良かった。',
            '雰囲気が良くて、デートにも使えそうです。',
            '少し待ち時間がありましたが、料理は美味しかったです。',
            'コスパが良くて、学生にもおすすめです。',
            'メニューが豊富で選ぶのが楽しかったです。',
            '店員さんが親切で、気持ちよく食事できました。',
            '味は普通でしたが、立地が良いのでまた利用するかも。',
            '期待していたより普通でした。',
            '新鮮な食材を使っていて、とても美味しかったです。',
            '清潔感があって、安心して食事できました。',
            '友人と楽しく食事できました。おすすめです。',
            '量が多くて、お腹いっぱいになりました。',
            '季節限定メニューが美味しかったです。',
        ];

        return $comments[array_rand($comments)];
    }
}
