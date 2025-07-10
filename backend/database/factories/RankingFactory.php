<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ranking>
 */
class RankingFactory extends Factory
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
            'category_id' => Category::factory(),
            'rank_position' => $this->faker->numberBetween(1, 10),
            'is_public' => $this->faker->boolean(70), // 70% chance to be public
            'title' => $this->faker->optional(0.6)->sentence(3),
            'description' => $this->faker->optional(0.4)->paragraph(),
        ];
    }

    /**
     * Indicate that the ranking is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the ranking is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the ranking is a top position.
     */
    public function topPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'rank_position' => $this->faker->numberBetween(1, 3),
            'title' => $this->faker->randomElement([
                'マイベスト1位',
                'No.1のお店',
                'トップランク',
                '最高の一軒',
            ]),
        ]);
    }

    /**
     * Indicate that the ranking has detailed information.
     */
    public function detailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(2, true),
        ]);
    }
}
