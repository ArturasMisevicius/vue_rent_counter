<?php

namespace Database\Factories;

use App\Models\PlatformOrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlatformOrganizationInvitation>
 */
class PlatformOrganizationInvitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_name' => fake()->company(),
            'admin_email' => fake()->safeEmail(),
            'plan_type' => fake()->randomElement(['basic', 'professional', 'enterprise']),
            'max_properties' => fake()->numberBetween(5, 100),
            'max_users' => fake()->numberBetween(3, 50),
            'token' => Str::random(64),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'invited_by' => User::factory()->superadmin(),
        ];
    }
}
