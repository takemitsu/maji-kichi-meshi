<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wishlist>
 */
class WishlistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'status' => 'want_to_go',
            'priority' => $this->faker->numberBetween(1, 3),
            'source_type' => $this->faker->randomElement(['review', 'shop_detail']),
        ];
    }

    /**
     * 行きたい状態
     */
    public function wantToGo(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'want_to_go',
        ]);
    }

    /**
     * 行った状態
     */
    public function visited(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'visited',
            'visited_at' => now(),
        ]);
    }
}
