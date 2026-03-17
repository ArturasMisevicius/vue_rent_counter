<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'code' => $code,
            'name' => 'Currency '.$code,
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function eur(): static
    {
        return $this->state(fn () => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => 'EUR',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
    }

    public function usd(): static
    {
        return $this->state(fn () => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => 'USD',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
    }

    public function gbp(): static
    {
        return $this->state(fn () => [
            'code' => 'GBP',
            'name' => 'British Pound Sterling',
            'symbol' => 'GBP',
            'decimal_places' => 2,
            'is_active' => true,
        ]);
    }
}
