<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrganizationActivityLog>
 */
class OrganizationActivityLogFactory extends Factory
{
    protected $model = OrganizationActivityLog::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'login',
                'logout',
                'data_access',
                'export',
                'update_settings',
            ]),
            'resource_type' => fake()->randomElement(['Property', 'Invoice', 'User', null]),
            'resource_id' => fake()->numberBetween(1, 50),
            'metadata' => ['ip' => fake()->ipv4(), 'user_agent' => fake()->userAgent()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
