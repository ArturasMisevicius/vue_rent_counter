<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->name();
        
        return [
            'tenant_id' => 1,
            'slug' => fake()->unique()->slug(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'property_id' => Property::factory(),
            'lease_start' => fake()->dateTimeBetween('-2 years', 'now'),
            'lease_end' => fake()->dateTimeBetween('now', '+2 years'),
        ];
    }
}
