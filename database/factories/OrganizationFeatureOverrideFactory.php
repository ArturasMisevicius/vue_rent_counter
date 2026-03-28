<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationFeatureOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationFeatureOverride>
 */
class OrganizationFeatureOverrideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'feature' => fake()->slug(2),
            'enabled' => fake()->boolean(),
            'reason' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
