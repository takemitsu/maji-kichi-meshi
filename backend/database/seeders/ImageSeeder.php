<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\Shop;
use App\Models\ShopImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ShopImagesのテストデータ作成
        $shops = Shop::limit(3)->get();

        foreach ($shops as $shop) {
            for ($i = 1; $i <= 2; $i++) {
                $uuid = Str::uuid()->toString();
                ShopImage::create([
                    'shop_id' => $shop->id,
                    'uuid' => $uuid,
                    'filename' => $uuid . '.jpg',
                    'original_name' => "shop_image_{$i}.jpg",
                    'thumbnail_path' => "images/shops/thumbnail/{$uuid}.jpg",
                    'small_path' => "images/shops/small/{$uuid}.jpg",
                    'medium_path' => "images/shops/medium/{$uuid}.jpg",
                    'large_path' => "images/shops/large/{$uuid}.jpg",
                    'original_path' => "images/shops/original/{$uuid}.jpg",
                    'mime_type' => 'image/jpeg',
                    'file_size' => rand(100000, 500000),
                    'image_sizes' => json_encode([
                        'thumbnail' => "/storage/images/shops/thumbnail/{$uuid}.jpg",
                        'small' => "/storage/images/shops/small/{$uuid}.jpg",
                        'medium' => "/storage/images/shops/medium/{$uuid}.jpg",
                        'large' => "/storage/images/shops/large/{$uuid}.jpg",
                    ]),
                    'sizes_generated' => ['thumbnail' => true],
                    'moderation_status' => collect(['published', 'under_review', 'rejected'])->random(),
                    'sort_order' => $i,
                ]);
            }
        }

        $this->command->info('ShopImages seeded successfully!');

        // ReviewImagesのテストデータ作成
        $reviews = Review::limit(5)->get();

        foreach ($reviews as $review) {
            for ($i = 1; $i <= rand(1, 3); $i++) {
                $uuid = Str::uuid()->toString();
                ReviewImage::create([
                    'review_id' => $review->id,
                    'uuid' => $uuid,
                    'filename' => $uuid . '.jpg',
                    'original_name' => "review_image_{$i}.jpg",
                    'thumbnail_path' => "images/reviews/thumbnail/{$uuid}.jpg",
                    'small_path' => "images/reviews/small/{$uuid}.jpg",
                    'medium_path' => "images/reviews/medium/{$uuid}.jpg",
                    'large_path' => "images/reviews/large/{$uuid}.jpg",
                    'original_path' => "images/reviews/original/{$uuid}.jpg",
                    'file_size' => rand(100000, 500000),
                    'mime_type' => 'image/jpeg',
                    'sizes_generated' => ['thumbnail' => true],
                    'moderation_status' => collect(['published', 'under_review', 'rejected'])->random(),
                    'moderation_notes' => null,
                ]);
            }
        }

        $this->command->info('ReviewImages seeded successfully!');
    }
}
