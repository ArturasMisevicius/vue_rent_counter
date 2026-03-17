<?php

namespace Database\Factories;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemConfiguration>
 */
class SystemConfigurationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => 'config.'.fake()->unique()->slug(),
            'value' => ['value' => fake()->word()],
            'type' => fake()->randomElement(['string', 'integer', 'boolean', 'json']),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['general', 'billing', 'security']),
            'validation_rules' => null,
            'default_value' => ['value' => null],
            'is_tenant_configurable' => false,
            'requires_restart' => false,
            'updated_by_admin_id' => User::factory()->superadmin(),
        ];
    }
}
