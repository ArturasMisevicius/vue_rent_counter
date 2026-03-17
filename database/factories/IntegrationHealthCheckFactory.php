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
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'label' => str($key)->replace('-', ' ')->title()->toString(),
            'status' => IntegrationHealthStatus::HEALTHY,
            'checked_at' => now()->subMinutes(fake()->numberBetween(1, 10)),
            'response_time_ms' => fake()->numberBetween(20, 400),
            'summary' => fake()->sentence(),
            'details' => ['source' => 'factory'],
        ];
    }
}
