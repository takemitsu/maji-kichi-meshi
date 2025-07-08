<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
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
            'rating' => $this->faker->numberBetween(1, 5),
            'repeat_intention' => $this->faker->randomElement(['また行く', 'わからん', '行かない']),
            'memo' => $this->faker->optional(0.7)->paragraph(),
            'visited_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the review has a high rating.
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
            'repeat_intention' => 'また行く',
        ]);
    }

    /**
     * Indicate that the review has a low rating.
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
            'repeat_intention' => $this->faker->randomElement(['わからん', '行かない']),
        ]);
    }

    /**
     * Indicate that the review is recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'visited_at' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ]);
    }
}
