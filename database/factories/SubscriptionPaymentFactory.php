<?php

namespace Database\Factories;

use App\Enums\SubscriptionDuration;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    public function definition(): array
    {
        $subscription = Subscription::factory();

        return [
            'organization_id' => Organization::factory(),
            'subscription_id' => $subscription,
            'duration' => SubscriptionDuration::MONTHLY,
            'amount' => fake()->randomFloat(2, 49, 499),
            'currency' => 'EUR',
            'paid_at' => now()->subDay(),
            'reference' => strtoupper(fake()->bothify('PAY-######')),
        ];
    }
}
