<?php

namespace Database\Seeders;

use App\Models\Ranking;
use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
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
        
        // 各ユーザーに対してランキングを作成
        foreach ($users->take(3) as $user) {
            // 総合ランキングを作成（デフォルトカテゴリを使用）
            $defaultCategory = $categories->first() ?: Category::first();
            if ($defaultCategory) {
                $this->createRankingForUser($user, $defaultCategory, $shops, '総合ランキング');
            }
            
            // カテゴリ別ランキングを作成
            foreach ($categories as $category) {
                $categoryShops = $shops->filter(function ($shop) use ($category) {
                    return $shop->categories->contains($category);
                });
                
                if ($categoryShops->isNotEmpty()) {
                    $this->createRankingForUser($user, $category, $categoryShops, $category->name . 'ランキング');
                }
            }
        }
        
        $this->command->info('Sample rankings created successfully.');
    }
    
    private function createRankingForUser($user, $category, $shops, $title): void
    {
        // ランダムに3-5店舗を選択
        $selectedShops = $shops->random(min(rand(3, 5), $shops->count()));
        
        foreach ($selectedShops as $index => $shop) {
            Ranking::create([
                'user_id' => $user->id,
                'shop_id' => $shop->id,
                'category_id' => $category->id,
                'rank_position' => $index + 1,
                'title' => $title,
                'description' => $this->generateRankingDescription($shop->name, $index + 1),
                'is_public' => rand(0, 1) === 1,
            ]);
        }
    }
    
    private function generateRankingDescription($shopName, $position): string
    {
        $descriptions = [
            1 => [
                '絶対的な1位！{shop}は本当に素晴らしいです。',
                'やっぱり{shop}が一番。何度でも行きたい。',
                '{shop}に勝る店はありません。完璧です。',
            ],
            2 => [
                '{shop}も本当に良いお店。1位と僅差です。',
                '2位の{shop}も素晴らしいクオリティです。',
                '{shop}は安定的に美味しい。信頼できます。',
            ],
            3 => [
                '{shop}は3位だけど、十分におすすめできます。',
                '3位の{shop}も良い選択肢だと思います。',
                '{shop}は特色があって面白いお店です。',
            ],
        ];
        
        $templates = $descriptions[$position] ?? [
            '{shop}は{position}位ですが、それでも良いお店です。',
            '{position}位の{shop}も魅力的です。',
        ];
        
        $template = $templates[array_rand($templates)];
        
        return str_replace(['{shop}', '{position}'], [$shopName, $position], $template);
    }
}