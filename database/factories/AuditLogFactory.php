<?php

namespace Database\Factories;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\Organization;
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
            'organization_id' => Organization::factory(),
            'actor_user_id' => User::factory()->superadmin(),
            'action' => fake()->randomElement(AuditLogAction::cases()),
            'subject_type' => Organization::class,
            'subject_id' => 1,
            'description' => fake()->sentence(),
            'ip_address' => fake()->ipv4(),
            'metadata' => ['source' => 'factory'],
            'occurred_at' => now()->subMinutes(fake()->numberBetween(1, 120)),
        ];
    }
}
