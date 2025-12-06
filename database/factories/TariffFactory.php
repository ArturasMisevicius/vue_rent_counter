<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Tariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tariff>
 */
class TariffFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'name' => fake()->words(3, true),
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.05, 0.30),
            ],
            'active_from' => now()->subMonths(6),
            'active_until' => null,
        ];
    }

    /**
     * Indicate that the tariff is for Ignitis with time-of-use pricing.
     */
    public function ignitis(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Ignitis Time-of-Use',
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                ],
                'weekend_logic' => 'apply_night_rate',
            ],
        ]);
    }

    /**
     * Indicate that the tariff is a flat rate tariff.
     */
    public function flat(): static
    {
        return $this->state(fn (array $attributes) => [
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.05, 0.30),
            ],
        ]);
    }

    /**
     * Indicate that the tariff is a time-of-use tariff.
     */
    public function timeOfUse(): static
    {
        return $this->state(fn (array $attributes) => [
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                ],
            ],
        ]);
    }

    /**
     * Set the active_from date.
     */
    public function activeFrom($date): static
    {
        return $this->state(fn (array $attributes) => [
            'active_from' => $date,
        ]);
    }

    /**
     * Set the active_until date.
     */
    public function activeUntil($date): static
    {
        return $this->state(fn (array $attributes) => [
            'active_until' => $date,
        ]);
    }

    /**
     * Indicate that the tariff is manual (no provider).
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => null,
            'remote_id' => null,
        ]);
    }

    /**
     * Set the remote_id for external system integration.
     */
    public function withRemoteId(string $remoteId): static
    {
        return $this->state(fn (array $attributes) => [
            'remote_id' => $remoteId,
        ]);
    }
}

