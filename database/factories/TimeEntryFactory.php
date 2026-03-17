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
        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'assignment_id' => null,
            'hours' => fake()->randomFloat(2, 0.5, 8),
            'description' => fake()->sentence(),
            'metadata' => ['billable' => true],
            'logged_at' => now(),
        ];
    }
}
