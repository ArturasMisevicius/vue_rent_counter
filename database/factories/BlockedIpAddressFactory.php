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
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'reason' => fake()->sentence(),
            'blocked_until' => now()->addDay(),
            'blocked_by_user_id' => User::factory()->superadmin(),
        ];
    }
}
