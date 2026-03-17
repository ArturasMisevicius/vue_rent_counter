<?php

namespace Database\Factories;

use App\Models\BlockedIpAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedIpAddress>
 */
class BlockedIpAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'reason' => fake()->sentence(),
            'blocked_by_user_id' => User::factory()->superadmin(),
            'blocked_at' => now(),
            'expires_at' => null,
        ];
    }
}
