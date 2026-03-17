<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->streetName().' Residences',
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
        ];
    }
}
