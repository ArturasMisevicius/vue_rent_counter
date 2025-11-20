<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'address' => fake()->streetAddress() . ', Vilnius',
            'total_apartments' => fake()->numberBetween(4, 50),
            'gyvatukas_summer_average' => null,
            'gyvatukas_last_calculated' => null,
        ];
    }
}
