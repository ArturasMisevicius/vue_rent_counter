<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->startOfDay();
        $plan = SubscriptionPlan::BASIC;
        $limits = $plan->limits();

        return [
            'organization_id' => Organization::factory(),
            'plan' => $plan,
            'status' => SubscriptionStatus::TRIALING,
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->copy()->addDays(14),
            'is_trial' => true,
            'property_limit_snapshot' => $limits['properties'],
            'tenant_limit_snapshot' => $limits['tenants'],
            'meter_limit_snapshot' => $limits['meters'],
            'invoice_limit_snapshot' => $limits['invoices'],
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::ACTIVE,
            'is_trial' => false,
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function forPlan(SubscriptionPlan $plan): static
    {
        $limits = $plan->limits();

        return $this->state([
            'plan' => $plan,
            'property_limit_snapshot' => $limits['properties'],
            'tenant_limit_snapshot' => $limits['tenants'],
            'meter_limit_snapshot' => $limits['meters'],
            'invoice_limit_snapshot' => $limits['invoices'],
        ]);
    }

    public function starter(): static
    {
        return $this->forPlan(SubscriptionPlan::STARTER);
    }

    public function basic(): static
    {
        return $this->forPlan(SubscriptionPlan::BASIC);
    }

    public function professional(): static
    {
        return $this->forPlan(SubscriptionPlan::PROFESSIONAL);
    }

    public function enterprise(): static
    {
        return $this->forPlan(SubscriptionPlan::ENTERPRISE);
    }

    public function custom(): static
    {
        return $this->forPlan(SubscriptionPlan::CUSTOM);
    }
}
