<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // 基本カテゴリ（既存）
            ['name' => 'ラーメン', 'slug' => 'ramen', 'type' => 'basic'],
            ['name' => '定食・食堂', 'slug' => 'teishoku', 'type' => 'basic'],
            ['name' => '居酒屋・バー', 'slug' => 'izakaya', 'type' => 'basic'],
            ['name' => 'カフェ・喫茶店', 'slug' => 'cafe', 'type' => 'basic'],
            ['name' => 'ファストフード', 'slug' => 'fastfood', 'type' => 'basic'],
            ['name' => 'その他', 'slug' => 'others', 'type' => 'basic'],

            // 基本カテゴリ（追加）
            ['name' => '中華料理', 'slug' => 'chinese', 'type' => 'basic'],
            ['name' => '寿司・和食', 'slug' => 'japanese', 'type' => 'basic'],
            ['name' => '焼肉・焼鳥', 'slug' => 'yakiniku', 'type' => 'basic'],
            ['name' => 'イタリアン・洋食', 'slug' => 'western', 'type' => 'basic'],
            ['name' => 'スイーツ・ベーカリー', 'slug' => 'sweets', 'type' => 'basic'],
            ['name' => 'カレー', 'slug' => 'curry', 'type' => 'basic'],
            ['name' => 'そば・うどん', 'slug' => 'noodles', 'type' => 'basic'],
            ['name' => 'エスニック', 'slug' => 'ethnic', 'type' => 'basic'],

            // 時間帯タグ
            ['name' => 'ランチ営業', 'slug' => 'lunch', 'type' => 'time'],
            ['name' => '深夜営業', 'slug' => 'late-night', 'type' => 'time'],
            ['name' => '朝営業', 'slug' => 'morning', 'type' => 'time'],

            // 特別カテゴリ（ランキング用）
            ['name' => '総合', 'slug' => 'overall', 'type' => 'ranking'],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
