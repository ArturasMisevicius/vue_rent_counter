<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAssignment>
 */
class TaskAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['assignee', 'reviewer', 'observer']),
            'assigned_at' => now(),
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
