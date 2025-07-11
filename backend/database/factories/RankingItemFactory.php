<?php

namespace Database\Factories;

use App\Models\Ranking;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RankingItem>
 */
class RankingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ranking_id' => Ranking::factory(),
            'shop_id' => Shop::factory(),
            'rank_position' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate that the item is a top position.
     */
    public function topPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank_position' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the item is a specific position.
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'rank_position' => $position,
        ]);
    }
}
