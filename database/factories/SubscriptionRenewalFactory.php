<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\SubscriptionRenewal>
 */
class SubscriptionRenewalFactory extends Factory
{
    protected $model = SubscriptionRenewal::class;

    public function definition(): array
    {
        $defaultMethod = fake()->randomElement(['manual', 'automatic']);
        $defaultPeriod = fake()->randomElement(['monthly', 'quarterly', 'annually']);

        $oldExpiresAt = Carbon::instance(fake()->dateTimeBetween('-1 year', '-1 day'));

        return [
            'subscription_id' => Subscription::factory(),
            'method' => $defaultMethod,
            'period' => $defaultPeriod,
            'old_expires_at' => $oldExpiresAt,
            'new_expires_at' => function (array $attributes) use ($defaultPeriod): Carbon {
                $period = $attributes['period'] ?? $defaultPeriod;
                $old = Carbon::parse($attributes['old_expires_at']);

                return match ($period) {
                    'monthly' => $old->copy()->addMonth(),
                    'quarterly' => $old->copy()->addMonths(3),
                    default => $old->copy()->addYear(),
                };
            },
            'duration_days' => function (array $attributes): int {
                $old = Carbon::parse($attributes['old_expires_at']);
                $new = Carbon::parse($attributes['new_expires_at']);

                return (int) $old->diffInDays($new, true);
            },
            'user_id' => function (array $attributes) use ($defaultMethod) {
                $method = $attributes['method'] ?? $defaultMethod;

                return $method === 'manual' ? User::factory() : null;
            },
            'notes' => function (array $attributes) use ($defaultMethod): ?string {
                $method = $attributes['method'] ?? $defaultMethod;

                return $method === 'manual' ? fake()->sentence() : null;
            },
        ];
    }
}

