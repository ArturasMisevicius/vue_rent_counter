<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'project_id' => Project::factory()->for($organization),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'review', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'created_by_user_id' => User::factory()->admin()->for($organization),
            'due_date' => now()->addWeek()->toDateString(),
            'completed_at' => null,
            'estimated_hours' => fake()->randomFloat(2, 1, 12),
            'actual_hours' => 0,
            'checklist' => null,
        ];
    }
}
