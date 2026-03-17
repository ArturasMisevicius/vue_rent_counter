<?php

namespace Database\Factories;

use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotificationDelivery>
 */
class PlatformNotificationDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'platform_notification_id' => PlatformNotification::factory(),
            'user_id' => User::factory()->superadmin(),
            'channel' => 'database',
            'delivered_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ];
    }
}
