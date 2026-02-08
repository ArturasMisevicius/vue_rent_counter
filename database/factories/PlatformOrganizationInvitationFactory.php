<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlanType;
use App\Models\PlatformOrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\PlatformOrganizationInvitation>
 */
class PlatformOrganizationInvitationFactory extends Factory
{
    protected $model = PlatformOrganizationInvitation::class;

    public function definition(): array
    {
        $planType = fake()->randomElement(SubscriptionPlanType::cases());
        
        return [
            'organization_name' => fake()->company(),
            'admin_email' => fake()->unique()->safeEmail(),
            'plan_type' => $planType,
            'max_properties' => match($planType) {
                SubscriptionPlanType::BASIC => 10,
                SubscriptionPlanType::PROFESSIONAL => 50,
                SubscriptionPlanType::ENTERPRISE => 200,
            },
            'max_users' => match($planType) {
                SubscriptionPlanType::BASIC => 5,
                SubscriptionPlanType::PROFESSIONAL => 20,
                SubscriptionPlanType::ENTERPRISE => 100,
            },
            'token' => Str::random(64),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'invited_by' => User::factory(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
