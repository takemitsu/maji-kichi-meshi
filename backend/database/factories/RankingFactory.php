<?php

namespace Database\Factories;

use App\Models\Category;
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
            'category_id' => Category::factory(),
            'is_public' => $this->faker->boolean(70), // 70% chance to be public
            'title' => $this->faker->sentence(3),
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
     * Indicate that the ranking has a specific title pattern.
     */
    public function topRanking(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement([
                'マイベストランキング',
                'お気に入りの店ランキング',
                'おすすめ店舗ランキング',
                'リピート確定ランキング',
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
