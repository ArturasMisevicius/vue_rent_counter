<?php

namespace Database\Factories;

use App\Enums\IntegrationHealthStatus;
use App\Models\IntegrationHealthCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationHealthCheck>
 */
class IntegrationHealthCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'status' => IntegrationHealthStatus::HEALTHY,
            'summary' => 'Waiting for scheduled probe.',
            'checked_at' => now(),
            'metadata' => [],
        ];
    }
}
