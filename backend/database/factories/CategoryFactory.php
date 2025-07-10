<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => $this->faker->randomElement(['basic', 'time', 'ranking']),
        ];
    }

    /**
     * Indicate that the category is a basic type.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'basic',
        ]);
    }

    /**
     * Indicate that the category is a time type.
     */
    public function time(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'time',
        ]);
    }

    /**
     * Indicate that the category is a ranking type.
     */
    public function ranking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ranking',
        ]);
    }
}
