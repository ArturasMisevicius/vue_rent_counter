<?php

namespace Database\Factories;

use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
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
            'role' => fake()->randomElement([
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
                UserRole::TENANT->value,
                'viewer',
            ]),
            'status' => ManagerMembershipStatus::ACTIVE,
            'permissions' => null,
            'permissions_preset' => 'read_only',
            'joined_at' => now()->subDays(fake()->numberBetween(1, 45)),
            'left_at' => null,
            'is_active' => true,
            'invited_by' => User::factory()->superadmin(),
            'invited_by_user_id' => null,
            'invited_at' => now()->subDays(fake()->numberBetween(1, 45)),
            'accepted_at' => now()->subDays(fake()->numberBetween(1, 44)),
            'disabled_at' => null,
        ];
    }
}
