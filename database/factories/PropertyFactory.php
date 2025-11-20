<?php

namespace Database\Factories;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'address' => fake()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
            'building_id' => \App\Models\Building::factory(),
        ];
    }
}
