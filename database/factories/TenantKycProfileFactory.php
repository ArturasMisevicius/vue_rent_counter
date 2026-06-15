<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantKycProfileStatus;
use App\Models\Organization;
use App\Models\TenantKycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantKycProfile>
 */
class TenantKycProfileFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);

        return [
            'organization_id' => $organization,
            'tenant_id' => $tenant,
            'status' => TenantKycProfileStatus::NOT_STARTED,
            'submitted_at' => null,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'expires_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state([
            'status' => TenantKycProfileStatus::VERIFIED,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);
    }
}
