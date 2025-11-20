<?php

namespace Database\Factories;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meter>
 */
class MeterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Meter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            MeterType::ELECTRICITY,
            MeterType::WATER_COLD,
            MeterType::WATER_HOT,
            MeterType::HEATING,
        ]);

        return [
            'tenant_id' => 1,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => $type,
            'property_id' => Property::factory(),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => $type === MeterType::ELECTRICITY ? fake()->boolean(30) : false,
        ];
    }
}
