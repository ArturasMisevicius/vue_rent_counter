<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
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
    public function configure(): static
    {
        return $this->afterMaking(function (Project $project): void {
            if ($project->organization_id === null) {
                $project->organization_id = Organization::factory()->create()->id;
            }

            if ($project->property_id !== null) {
                $property = Property::query()
                    ->select(['id', 'organization_id', 'building_id'])
                    ->find($project->property_id);

                if ($property !== null) {
                    $project->organization_id = $property->organization_id;
                    $project->building_id = $property->building_id;
                }
            }

            if ($project->building_id === null) {
                $project->building_id = Building::factory()->create([
                    'organization_id' => $project->organization_id,
                ])->id;
            }

            if ($project->property_id === null) {
                $project->property_id = Property::factory()->create([
                    'organization_id' => $project->organization_id,
                    'building_id' => $project->building_id,
                ])->id;
            }

            if ($project->created_by_user_id === null) {
                $project->created_by_user_id = User::factory()->admin()->create([
                    'organization_id' => $project->organization_id,
                ])->id;
            }

            if ($project->manager_id === null) {
                $project->manager_id = User::factory()->manager()->create([
                    'organization_id' => $project->organization_id,
                ])->id;
            }
        });
    }

    public function definition(): array
    {
        $estimatedStart = now()->subWeek()->toDateString();
        $estimatedEnd = now()->addWeeks(2)->toDateString();

        return [
            'organization_id' => Organization::factory(),
            'building_id' => null,
            'property_id' => null,
            'created_by_user_id' => null,
            'assigned_to_user_id' => null,
            'manager_id' => null,
            'approved_by' => null,
            'name' => fake()->words(3, true),
            'reference_number' => null,
            'description' => fake()->sentence(),
            'type' => fake()->randomElement([
                ProjectType::MAINTENANCE,
                ProjectType::RENOVATION,
                ProjectType::INSPECTION,
                ProjectType::COMPLIANCE,
            ]),
            'status' => ProjectStatus::DRAFT,
            'priority' => fake()->randomElement([
                ProjectPriority::LOW,
                ProjectPriority::MEDIUM,
                ProjectPriority::HIGH,
            ]),
            'start_date' => $estimatedStart,
            'estimated_start_date' => $estimatedStart,
            'actual_start_date' => null,
            'due_date' => $estimatedEnd,
            'estimated_end_date' => $estimatedEnd,
            'actual_end_date' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'budget' => fake()->randomFloat(2, 200, 5000),
            'budget_amount' => fake()->randomFloat(2, 200, 5000),
            'actual_cost' => 0,
            'completion_percentage' => 0,
            'requires_approval' => false,
            'approved_at' => null,
            'cost_passed_to_tenant' => false,
            'external_contractor' => null,
            'contractor_contact' => null,
            'contractor_reference' => null,
            'notes' => null,
            'metadata' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => ProjectStatus::IN_PROGRESS,
            'actual_start_date' => today(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => ProjectStatus::COMPLETED,
            'actual_start_date' => today()->subWeek(),
            'actual_end_date' => today(),
            'completed_at' => now(),
            'completion_percentage' => 100,
        ]);
    }
}
