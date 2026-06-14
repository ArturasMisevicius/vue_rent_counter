<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationDeliveryLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationDeliveryLog>
 */
class NotificationDeliveryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_id' => (string) Str::uuid(),
            'channel' => fake()->randomElement(['database', 'mail']),
            'status' => fake()->randomElement(['attempted', 'delivered', 'failed']),
            'attempted_at' => now(),
            'delivered_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ];
    }
}
