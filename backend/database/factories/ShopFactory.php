<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' ' . $this->faker->randomElement(['店', 'Restaurant', 'Cafe', 'Bar']),
            'description' => $this->faker->sentence(),
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude(35.65, 35.75), // 吉祥寺周辺
            'longitude' => $this->faker->longitude(139.55, 139.65), // 吉祥寺周辺
            'phone' => $this->faker->phoneNumber,
            'website' => $this->faker->optional()->url,
            'google_place_id' => $this->faker->optional()->uuid,
            'is_closed' => $this->faker->boolean(10), // 10% chance of being closed
        ];
    }

    /**
     * Indicate that the shop is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }

    /**
     * Indicate that the shop is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => false,
        ]);
    }
}
