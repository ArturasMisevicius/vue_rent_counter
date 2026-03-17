<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotificationDelivery>
 */
class PlatformNotificationDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform_notification_id' => PlatformNotification::factory(),
            'user_id' => User::factory()->admin(),
            'organization_id' => Organization::factory(),
            'status' => 'pending',
            'delivered_at' => null,
            'failure_reason' => null,
            'metadata' => [],
        ];
    }
}
