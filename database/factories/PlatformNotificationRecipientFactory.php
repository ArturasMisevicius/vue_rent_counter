<?php

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
    public function definition(): array
    {
        return [
            'platform_notification_id' => PlatformNotification::factory(),
            'organization_id' => Organization::factory(),
            'email' => fake()->safeEmail(),
            'delivery_status' => 'pending',
            'sent_at' => null,
            'read_at' => null,
            'failure_reason' => null,
        ];
    }
}
