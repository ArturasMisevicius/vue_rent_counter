<?php

namespace Database\Factories;

use App\Models\Meter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeterReading>
 */
class MeterReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'meter_id' => Meter::factory(),
            'reading_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'value' => fake()->randomFloat(2, 0, 10000),
            'zone' => null,
            'entered_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the reading has a zone.
     */
    public function withZone(string $zone): static
    {
        return $this->state(fn (array $attributes) => [
            'zone' => $zone,
        ]);
    }
}
