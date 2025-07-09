<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\ShopImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopImageFactory extends Factory
{
    protected $model = ShopImage::class;

    public function definition()
    {
        return [
            'shop_id' => Shop::factory(),
            'uuid' => $this->faker->uuid,
            'filename' => $this->faker->uuid . '.jpg',
            'original_name' => $this->faker->word . '.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'image_sizes' => [
                'thumbnail' => 'http://localhost/storage/images/shops/thumbnail/test.jpg',
                'small' => 'http://localhost/storage/images/shops/small/test.jpg',
                'medium' => 'http://localhost/storage/images/shops/medium/test.jpg',
                'large' => 'http://localhost/storage/images/shops/large/test.jpg',
            ],
            'status' => $this->faker->randomElement(['published', 'under_review', 'rejected']),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'published',
            ];
        });
    }

    public function underReview()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'under_review',
            ];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
            ];
        });
    }
}