<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PropertyAssignmentStatus;
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
            'status' => PropertyAssignmentStatus::ACTIVE,
            'is_primary' => true,
            'occupants_count' => null,
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
            'move_out_date' => null,
            'billing_start_date' => null,
            'billing_end_date' => null,
            'move_out_reason' => null,
            'move_out_scheduled_by_user_id' => null,
            'move_out_completed_by_user_id' => null,
            'move_out_completed_at' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
