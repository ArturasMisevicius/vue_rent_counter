<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $acceptanceToken = OrganizationInvitation::issueToken();
        $tokenHash = OrganizationInvitation::hashToken($acceptanceToken);
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'tenant_id' => null,
            'inviter_user_id' => User::factory()->admin()->for($organization),
            'invited_by_user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::TENANT,
            'full_name' => fake()->name(),
            'token' => $tokenHash,
            'token_hash' => $tokenHash,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'revoked_at' => null,
        ];
    }
}
