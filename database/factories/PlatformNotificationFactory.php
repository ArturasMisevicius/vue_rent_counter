<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotification>
 */
class PlatformNotificationFactory extends Factory
{
    protected $model = PlatformNotification::class;

    public function definition(): array
    {
        $targetType = fake()->randomElement(['all', 'plan', 'organization']);

        return [
            'title' => fake()->sentence(5),
            'message' => fake()->paragraph(),
            'target_type' => $targetType,
            'target_criteria' => match ($targetType) {
                'plan' => ['basic', 'professional'],
                'organization' => [1],
                default => null,
            },
            'status' => fake()->randomElement(['draft', 'scheduled', 'sent']),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+5 days'),
            'sent_at' => fake()->optional()->dateTimeBetween('-3 days', 'now'),
            'created_by' => function (): int {
                return User::query()->where('role', 'superadmin')->value('id')
                    ?? User::factory()->superadmin()->create()->id;
            },
            'delivery_stats' => [
                'sent' => fake()->numberBetween(0, 20),
                'read' => fake()->numberBetween(0, 15),
            ],
            'failure_reason' => null,
        ];
    }
}
