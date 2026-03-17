<?php

namespace Database\Factories;

use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SystemTenant>
 */
class SystemTenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company().' Platform';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'domain' => fake()->optional()->domainName(),
            'status' => fake()->randomElement(['pending', 'active', 'suspended']),
            'subscription_plan' => fake()->randomElement(['starter', 'growth', 'enterprise']),
            'settings' => [
                'timezone' => 'Europe/Vilnius',
            ],
            'resource_quotas' => [
                'max_users' => 25,
                'max_storage_gb' => 5,
            ],
            'billing_info' => [
                'currency' => 'EUR',
            ],
            'primary_contact_email' => fake()->safeEmail(),
            'created_by_admin_id' => User::factory()->superadmin(),
        ];
    }
}
