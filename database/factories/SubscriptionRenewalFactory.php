<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SubscriptionRenewal>
 */
class SubscriptionRenewalFactory extends Factory
{
    public function definition(): array
    {
        $method = fake()->randomElement(['manual', 'automatic']);
        $period = fake()->randomElement(['monthly', 'quarterly', 'annually']);
        $oldExpiresAt = Carbon::instance(fake()->dateTimeBetween('-2 months', '+2 weeks'));
        $newExpiresAt = match ($period) {
            'monthly' => $oldExpiresAt->copy()->addMonth(),
            'quarterly' => $oldExpiresAt->copy()->addMonths(3),
            default => $oldExpiresAt->copy()->addYear(),
        };

        return [
            'subscription_id' => Subscription::factory(),
            'user_id' => $method === 'manual' ? User::factory() : null,
            'method' => $method,
            'period' => $period,
            'old_expires_at' => $oldExpiresAt,
            'new_expires_at' => $newExpiresAt,
            'duration_days' => $oldExpiresAt->diffInDays($newExpiresAt),
            'notes' => $method === 'manual' ? fake()->sentence() : null,
        ];
    }
}
