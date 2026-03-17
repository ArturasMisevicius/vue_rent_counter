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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory()->admin(),
            'type' => SecurityViolationType::SUSPICIOUS_LOGIN,
            'severity' => SecurityViolationSeverity::MEDIUM,
            'ip_address' => fake()->ipv4(),
            'description' => fake()->sentence(),
            'context' => [
                'source' => 'factory',
            ],
            'occurred_at' => now(),
            'resolved_at' => null,
        ];
    }
}
