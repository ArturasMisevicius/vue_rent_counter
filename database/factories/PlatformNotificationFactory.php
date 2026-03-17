<?php

namespace Database\Factories;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotification>
 */
class PlatformNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory()->superadmin(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'severity' => PlatformNotificationSeverity::INFO,
            'status' => PlatformNotificationStatus::DRAFT,
            'target_scope' => 'all',
            'sent_at' => null,
            'metadata' => [],
        ];
    }
}
