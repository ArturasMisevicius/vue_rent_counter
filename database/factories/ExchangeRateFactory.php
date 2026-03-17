<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'from_currency_id' => Currency::factory(),
            'to_currency_id' => Currency::factory(),
            'rate' => fake()->randomFloat(6, 0.100000, 2.500000),
            'effective_date' => fake()->date(),
            'source' => fake()->randomElement(['manual', 'api', 'seeder']),
            'is_active' => true,
        ];
    }

    public function today(): static
    {
        return $this->state(fn () => [
            'effective_date' => now()->toDateString(),
        ]);
    }
}
