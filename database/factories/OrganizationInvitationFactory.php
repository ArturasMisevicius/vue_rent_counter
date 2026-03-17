<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'inviter_user_id' => User::factory()->admin()->for($organization),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::TENANT,
            'full_name' => fake()->name(),
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }
}
