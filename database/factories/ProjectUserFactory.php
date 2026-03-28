<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectTeamRole;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectUser>
 */
class ProjectUserFactory extends Factory
{
    public function definition(): array
    {
        $project = Project::factory();

        return [
            'project_id' => $project,
            'user_id' => User::factory(),
            'role' => ProjectTeamRole::CONTRIBUTOR,
            'invited_at' => now(),
            'invited_by' => null,
        ];
    }
}
