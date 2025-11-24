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

    /**
     * Attach to a specific meter and keep tenant_id aligned.
     */
    public function forMeter(Meter $meter): static
    {
        return $this->state(fn ($attributes) => [
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'entered_by' => $attributes['entered_by']
                ?? User::factory()->state(['tenant_id' => $meter->tenant_id]),
        ]);
    }

    /**
     * Ensure tenant consistency for related meter and user.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\MeterReading $reading) {
            $meter = $reading->meter ?? Meter::find($reading->meter_id);

            if ($meter && $reading->tenant_id !== $meter->tenant_id) {
                $reading->tenant_id = $meter->tenant_id;
            }

            if ($reading->enteredBy && $reading->enteredBy->tenant_id !== $reading->tenant_id) {
                $reading->enteredBy->tenant_id = $reading->tenant_id;
            }
        })->afterCreating(function (\App\Models\MeterReading $reading) {
            $meter = $reading->meter ?? Meter::find($reading->meter_id);

            if ($meter && $reading->tenant_id !== $meter->tenant_id) {
                $reading->update(['tenant_id' => $meter->tenant_id]);
            }

            if ($reading->enteredBy && $reading->enteredBy->tenant_id !== $reading->tenant_id) {
                $reading->enteredBy->update(['tenant_id' => $reading->tenant_id]);
            }
        });
    }
}
