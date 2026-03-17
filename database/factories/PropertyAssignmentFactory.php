<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyAssignment>
 */
class PropertyAssignmentFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'property_id' => Property::factory()->for($organization),
            'tenant_user_id' => User::factory()->tenant()->for($organization),
            'unit_area_sqm' => fake()->randomFloat(2, 25, 180),
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ];
    }
}
