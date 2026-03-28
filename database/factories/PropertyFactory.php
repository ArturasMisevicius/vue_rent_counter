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
    public function configure(): static
    {
        return $this->afterMaking(function (Property $property): void {
            if ($property->organization_id === null) {
                $property->organization_id = Organization::factory()->create()->id;
            }

            if ($property->building_id === null) {
                $property->building_id = Building::factory()->create([
                    'organization_id' => $property->organization_id,
                ])->id;
            }

            $building = Building::query()
                ->select(['id', 'organization_id'])
                ->find($property->building_id);

            if ($building !== null) {
                $property->organization_id = $building->organization_id;
            }
        });
    }

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'building_id' => null,
            'name' => 'Property '.fake()->unique()->numberBetween(1, 999),
            'unit_number' => (string) fake()->unique()->numberBetween(1, 200),
            'type' => fake()->randomElement(PropertyType::cases()),
            'floor_area_sqm' => fake()->randomFloat(2, 25, 180),
        ];
    }

    public function unit(string $name, string $unitNumber, PropertyType $type, float $floorArea): static
    {
        return $this->state([
            'name' => $name,
            'unit_number' => $unitNumber,
            'type' => $type,
            'floor_area_sqm' => $floorArea,
        ]);
    }
}
