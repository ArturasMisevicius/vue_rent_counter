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

        return [
            'organization_id' => Organization::factory(),
            'plan' => SubscriptionPlan::BASIC,
            'status' => SubscriptionStatus::TRIALING,
            'starts_at' => $startsAt,
            'expires_at' => $startsAt->copy()->addDays(14),
            'is_trial' => true,
        ];
    }
}
