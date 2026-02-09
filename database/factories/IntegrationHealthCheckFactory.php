<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IntegrationStatus;
use App\Models\IntegrationHealthCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationHealthCheck>
 */
class IntegrationHealthCheckFactory extends Factory
{
    protected $model = IntegrationHealthCheck::class;

    public function definition(): array
    {
        $service = fake()->randomElement(['stripe', 'mail', 'storage', 'sms']);

        return [
            'service_name' => $service,
            'endpoint' => "https://api.example.test/{$service}/health",
            'status' => fake()->randomElement(IntegrationStatus::cases()),
            'response_time_ms' => fake()->numberBetween(40, 1500),
            'error_message' => null,
            'checked_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
        ];
    }
}
