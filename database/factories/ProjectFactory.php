<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $building = Building::factory()->for($organization);
        $property = Property::factory()->for($organization)->for($building);

        return [
            'organization_id' => $organization,
            'property_id' => $property,
            'building_id' => $building,
            'created_by_user_id' => User::factory()->admin()->for($organization),
            'assigned_to_user_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['maintenance', 'improvement', 'inspection', 'repair', 'upgrade']),
            'status' => fake()->randomElement(['draft', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'start_date' => now()->subWeek()->toDateString(),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'completed_at' => null,
            'budget' => fake()->randomFloat(2, 200, 5000),
            'actual_cost' => 0,
            'metadata' => null,
        ];
    }
}
