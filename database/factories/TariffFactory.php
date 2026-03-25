<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Tariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tariff>
 */
class TariffFactory extends Factory
{
    public function definition(): array
    {
        $provider = Provider::factory();

        return [
            'provider_id' => $provider,
            'remote_id' => strtoupper(fake()->bothify('TRF-####-??')),
            'name' => fake()->words(3, true),
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.05, 0.30),
            ],
            'active_from' => now()->subMonths(3),
            'active_until' => now()->addMonths(fake()->numberBetween(3, 12)),
        ];
    }

    public function flat(): static
    {
        return $this->state(fn () => [
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.05, 0.30),
            ],
        ]);
    }

    public function timeOfUse(): static
    {
        return $this->state(fn () => [
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
}
