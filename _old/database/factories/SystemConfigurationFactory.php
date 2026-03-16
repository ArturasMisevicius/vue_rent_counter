<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemConfiguration>
 */
class SystemConfigurationFactory extends Factory
{
    protected $model = SystemConfiguration::class;

    public function definition(): array
    {
        $key = fake()->unique()->slug(2, '_');
        $value = fake()->boolean() ? fake()->word() : (string) fake()->numberBetween(1, 999);

        return [
            'key' => "platform.{$key}",
            'value' => ['value' => $value],
            'type' => 'string',
            'description' => fake()->sentence(),
            'is_tenant_configurable' => fake()->boolean(20),
            'requires_restart' => fake()->boolean(10),
            'updated_by_admin_id' => function (): ?int {
                return User::query()->where('role', 'superadmin')->value('id')
                    ?? User::query()->where('role', 'admin')->value('id')
                    ?? null;
            },
        ];
    }
}
