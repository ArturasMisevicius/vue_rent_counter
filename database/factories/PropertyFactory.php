<?php

namespace Database\Factories;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'building_id' => Building::factory()->for($organization),
            'name' => 'Property '.fake()->unique()->numberBetween(1, 999),
            'unit_number' => (string) fake()->unique()->numberBetween(1, 200),
            'type' => fake()->randomElement(PropertyType::cases()),
            'floor_area_sqm' => fake()->randomFloat(2, 25, 180),
        ];
    }
}
