<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotificationRecipient>
 */
class PlatformNotificationRecipientFactory extends Factory
{
    protected $model = PlatformNotificationRecipient::class;

    public function definition(): array
    {
        return [
            'platform_notification_id' => PlatformNotification::factory(),
            'organization_id' => function (): int {
                return Organization::query()->value('id') ?? Organization::factory()->create()->id;
            },
            'email' => fake()->safeEmail(),
            'delivery_status' => fake()->randomElement(['pending', 'sent', 'read']),
            'sent_at' => fake()->optional()->dateTimeBetween('-3 days', 'now'),
            'read_at' => fake()->optional()->dateTimeBetween('-2 days', 'now'),
            'failure_reason' => null,
        ];
    }
}
