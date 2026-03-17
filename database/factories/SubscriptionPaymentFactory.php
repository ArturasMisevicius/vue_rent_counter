<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'organization_id' => Organization::factory(),
            'amount' => fake()->numberBetween(990, 99000),
            'currency' => 'EUR',
            'status' => 'paid',
            'reference' => (string) Str::uuid(),
            'paid_at' => now(),
            'metadata' => [],
        ];
    }
}
