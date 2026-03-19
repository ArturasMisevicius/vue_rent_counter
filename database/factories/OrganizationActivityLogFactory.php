<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationActivityLog>
 */
class OrganizationActivityLogFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'user_id' => User::factory()->manager()->for($organization),
            'action' => fake()->randomElement([
                'login',
                'logout',
                'data_access',
                'export',
                'update_settings',
            ]),
            'resource_type' => fake()->randomElement([Organization::class, Property::class, Invoice::class, null]),
            'resource_id' => fake()->numberBetween(1, 50),
            'metadata' => ['source' => 'factory'],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
