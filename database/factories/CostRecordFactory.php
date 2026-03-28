<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectCostRecordType;
use App\Models\CostRecord;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostRecord>
 */
class CostRecordFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'project_id' => Project::factory()->for($organization),
            'created_by_user_id' => User::factory()->admin()->for($organization),
            'type' => ProjectCostRecordType::EXPENSE,
            'description' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 25, 500),
            'incurred_on' => now()->toDateString(),
            'metadata' => null,
        ];
    }
}
