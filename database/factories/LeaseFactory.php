<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Lease;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $startDate = now()->subMonths(2);

        return [
            'organization_id' => $organization,
            'property_id' => Property::factory()->for($organization)->for(Building::factory()->for($organization)),
            'tenant_user_id' => User::factory()->tenant()->for($organization),
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addYear()->toDateString(),
            'monthly_rent' => fake()->randomFloat(2, 350, 1400),
            'deposit' => fake()->randomFloat(2, 500, 2200),
            'is_active' => true,
        ];
    }
}
