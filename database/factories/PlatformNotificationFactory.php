<?php

namespace Database\Factories;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Models\PlatformNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotification>
 */
class PlatformNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'severity' => fake()->randomElement(PlatformNotificationSeverity::cases()),
            'status' => PlatformNotificationStatus::DRAFT,
            'scheduled_for' => null,
            'sent_at' => null,
        ];
    }
}
