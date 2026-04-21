<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
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
            'code' => $this->faker->unique()->currencyCode(),
            'name' => $this->faker->word(),
            'symbol' => $this->faker->randomElement(['$', '€', '£', 'Rs.', '¥']),
            'country' => $this->faker->country(),
            'conversion_rate' => $this->faker->randomFloat(4, 1, 300),
            'is_base' => false,
            'is_active' => true,
        ];
    }
}
