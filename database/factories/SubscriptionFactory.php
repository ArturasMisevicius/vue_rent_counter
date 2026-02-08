<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $planType = fake()->randomElement(SubscriptionPlanType::values());
        $limits = $this->getPlanLimits($planType);

        return [
            'user_id' => User::factory(),
            'plan_type' => $planType,
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => $limits['max_properties'],
            'max_tenants' => $limits['max_tenants'],
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addYear(),
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED->value,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDays(10),
        ]);
    }

    /**
     * Indicate that the subscription is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::SUSPENDED->value,
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED->value,
        ]);
    }

    /**
     * Indicate that the subscription is for a basic plan.
     */
    public function basic(): static
    {
        $limits = $this->getPlanLimits(SubscriptionPlanType::BASIC->value);
        
        return $this->state(fn (array $attributes) => [
            'plan_type' => SubscriptionPlanType::BASIC->value,
            'max_properties' => $limits['max_properties'],
            'max_tenants' => $limits['max_tenants'],
        ]);
    }

    /**
     * Indicate that the subscription is for a professional plan.
     */
    public function professional(): static
    {
        $limits = $this->getPlanLimits(SubscriptionPlanType::PROFESSIONAL->value);
        
        return $this->state(fn (array $attributes) => [
            'plan_type' => SubscriptionPlanType::PROFESSIONAL->value,
            'max_properties' => $limits['max_properties'],
            'max_tenants' => $limits['max_tenants'],
        ]);
    }

    /**
     * Indicate that the subscription is for an enterprise plan.
     */
    public function enterprise(): static
    {
        $limits = $this->getPlanLimits(SubscriptionPlanType::ENTERPRISE->value);
        
        return $this->state(fn (array $attributes) => [
            'plan_type' => SubscriptionPlanType::ENTERPRISE->value,
            'max_properties' => $limits['max_properties'],
            'max_tenants' => $limits['max_tenants'],
        ]);
    }

    /**
     * Indicate that the subscription expires soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(5),
        ]);
    }

    /**
     * Get the limits for a given plan type.
     *
     * @param string $planType
     * @return array
     */
    protected function getPlanLimits(string $planType): array
    {
        $limits = [
            'basic' => [
                'max_properties' => 10,
                'max_tenants' => 50,
            ],
            'professional' => [
                'max_properties' => 50,
                'max_tenants' => 200,
            ],
            'enterprise' => [
                'max_properties' => 999999,
                'max_tenants' => 999999,
            ],
        ];

        return $limits[$planType] ?? $limits['basic'];
    }
}
