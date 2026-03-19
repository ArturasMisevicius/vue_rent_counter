<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationUser>
 */
class OrganizationUserFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'user_id' => User::factory()->manager()->for($organization),
            'role' => fake()->randomElement(['admin', 'manager', 'viewer', 'contributor']),
            'permissions' => ['reports.view'],
            'joined_at' => now()->subDays(fake()->numberBetween(1, 45)),
            'left_at' => null,
            'is_active' => true,
            'invited_by' => User::factory()->superadmin(),
        ];
    }
}
