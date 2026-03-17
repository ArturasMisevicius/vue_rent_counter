<?php

namespace Database\Factories;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->superadmin(),
            'action' => AuditLogAction::UPDATED,
            'auditable_type' => null,
            'auditable_id' => null,
            'description' => fake()->sentence(),
            'metadata' => [
                'source' => 'factory',
            ],
            'occurred_at' => now(),
        ];
    }
}
