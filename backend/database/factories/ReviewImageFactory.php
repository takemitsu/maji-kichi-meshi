<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewImage>
 */
class ReviewImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->uuid() . '.jpg';
        
        return [
            'filename' => $filename,
            'original_name' => $this->faker->word() . '.jpg',
            'thumbnail_path' => "images/reviews/thumbnail/{$filename}",
            'small_path' => "images/reviews/small/{$filename}",
            'medium_path' => "images/reviews/medium/{$filename}",
            'large_path' => "images/reviews/large/{$filename}",
            'file_size' => $this->faker->numberBetween(100000, 5000000), // 100KB to 5MB
            'mime_type' => 'image/jpeg',
        ];
    }
}
