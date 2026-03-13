<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
final class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency_id' => Currency::factory(),
            'to_currency_id' => Currency::factory(),
            'rate' => $this->faker->randomFloat(6, 0.1, 10.0),
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'source' => $this->faker->randomElement(['manual', 'api', 'seeder', 'external_provider']),
            'is_active' => $this->faker->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Indicate that the exchange rate is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the exchange rate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the exchange rate for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => now()->toDateString(),
        ]);
    }

    /**
     * Set the exchange rate for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date,
        ]);
    }

    /**
     * Set the source as manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'manual',
        ]);
    }

    /**
     * Set the source as API.
     */
    public function fromApi(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'api',
        ]);
    }

    /**
     * Set the source as external provider.
     */
    public function fromExternalProvider(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'external_provider',
        ]);
    }

    /**
     * Create an exchange rate between specific currencies.
     */
    public function between(Currency $fromCurrency, Currency $toCurrency): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
        ]);
    }

    /**
     * Create a realistic USD to EUR exchange rate.
     */
    public function usdToEur(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $this->faker->randomFloat(6, 0.80, 0.90),
        ]);
    }

    /**
     * Create a realistic EUR to USD exchange rate.
     */
    public function eurToUsd(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $this->faker->randomFloat(6, 1.10, 1.25),
        ]);
    }

    /**
     * Create a realistic USD to GBP exchange rate.
     */
    public function usdToGbp(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $this->faker->randomFloat(6, 0.70, 0.80),
        ]);
    }

    /**
     * Create a realistic USD to JPY exchange rate.
     */
    public function usdToJpy(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $this->faker->randomFloat(2, 100.0, 150.0),
        ]);
    }

    /**
     * Create an exchange rate for a specific currency pair.
     */
    public function forCurrencyPair(Currency $fromCurrency, Currency $toCurrency): static
    {
        return $this->state(fn (array $attributes) => [
            'from_currency_id' => $fromCurrency->id,
            'to_currency_id' => $toCurrency->id,
        ]);
    }

    /**
     * Set a specific exchange rate.
     */
    public function withRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => $rate,
        ]);
    }

    /**
     * Set the effective date for the exchange rate.
     */
    public function effectiveOn(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date->format('Y-m-d'),
        ]);
    }
}