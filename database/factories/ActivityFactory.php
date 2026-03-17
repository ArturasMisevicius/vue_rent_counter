<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $user = User::factory()->admin()->for($organization);
        $project = Project::factory()->for($organization)->for($user, 'creator');

        return [
            'organization_id' => $organization,
            'log_name' => 'activity',
            'description' => fake()->sentence(),
            'subject_type' => Project::class,
            'subject_id' => $project,
            'causer_type' => User::class,
            'causer_id' => $user,
            'properties' => ['source' => 'factory'],
            'event' => fake()->randomElement(['created', 'updated', 'commented']),
            'batch_uuid' => null,
        ];
    }
}
