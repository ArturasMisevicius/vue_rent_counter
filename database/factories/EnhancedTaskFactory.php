<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\EnhancedTask;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnhancedTask>
 */
class EnhancedTaskFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $building = Building::factory()->for($organization);
        $property = Property::factory()->for($organization)->for($building);

        return [
            'organization_id' => $organization,
            'project_id' => Project::factory()->for($organization)->for($property)->for($building),
            'property_id' => $property,
            'meter_id' => Meter::factory()->for($organization)->for($property),
            'created_by_user_id' => User::factory()->admin()->for($organization),
            'parent_enhanced_task_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['maintenance', 'reading', 'inspection', 'repair', 'installation']),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled', 'on_hold']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'estimated_hours' => fake()->randomFloat(2, 1, 10),
            'actual_hours' => 0,
            'estimated_cost' => fake()->randomFloat(2, 50, 1000),
            'actual_cost' => 0,
            'due_date' => now()->addDays(10),
            'started_at' => null,
            'completed_at' => null,
            'metadata' => null,
        ];
    }
}
