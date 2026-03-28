<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationLimitOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationLimitOverride>
 */
class OrganizationLimitOverrideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'dimension' => fake()->randomElement(OrganizationLimitOverride::SUPPORTED_DIMENSIONS),
            'value' => fake()->numberBetween(5, 500),
            'reason' => fake()->sentence(),
            'expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'created_by' => User::factory(),
        ];
    }
}
