<?php

namespace Database\Factories;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityViolation>
 */
class SecurityViolationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory()->tenant(),
            'type' => fake()->randomElement(SecurityViolationType::cases()),
            'severity' => fake()->randomElement(SecurityViolationSeverity::cases()),
            'ip_address' => fake()->ipv4(),
            'summary' => fake()->sentence(),
            'metadata' => ['source' => 'factory'],
            'occurred_at' => now()->subHours(fake()->numberBetween(1, 48)),
            'resolved_at' => null,
        ];
    }
}
