<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EnhancedTask;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnhancedTask>
 */
class EnhancedTaskFactory extends Factory
{
    protected $model = EnhancedTask::class;

    public function definition(): array
    {
        $creatorId = User::query()->where('tenant_id', 1)->value('id')
            ?? User::factory()->manager(1)->create()->id;

        return [
            'tenant_id' => $creatorId,
            'project_id' => null,
            'property_id' => Property::factory()->forTenantId(1),
            'meter_id' => null,
            'created_by' => $creatorId,
            'parent_task_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => fake()->randomElement(['maintenance', 'reading', 'inspection', 'repair', 'installation']),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'on_hold']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'estimated_hours' => fake()->randomFloat(2, 1, 16),
            'actual_hours' => fake()->randomFloat(2, 0, 16),
            'estimated_cost' => fake()->randomFloat(2, 50, 1200),
            'actual_cost' => fake()->randomFloat(2, 0, 1200),
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
            'started_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'completed_at' => null,
            'metadata' => [
                'source' => 'comprehensive-seeder',
                'category' => fake()->randomElement(['operations', 'maintenance']),
            ],
        ];
    }
}
