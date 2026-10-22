<?php

namespace Database\Factories;

use App\Models\OAuthProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OAuthProvider>
 */
class OAuthProviderFactory extends Factory
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
            'provider' => $this->faker->randomElement(['google', 'github', 'line', 'twitter']),
            'provider_id' => $this->faker->unique()->randomNumber(8),
            'provider_token' => $this->faker->sha256,
        ];
    }
}
