<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $task = Task::factory();

        return [
            'organization_id' => null,
            'user_id' => User::factory(),
            'task_id' => $task,
            'project_id' => null,
            'assignment_id' => null,
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'hourly_rate' => fake()->randomFloat(2, 25, 100),
            'cost_amount' => fake()->randomFloat(2, 25, 600),
            'description' => fake()->sentence(),
            'approval_status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
            'metadata' => ['billable' => true],
            'logged_at' => now(),
        ];
    }
}
