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

    /**
     * Force the property to belong to a specific tenant and align the building.
     */
    public function forTenantId(int $tenantId): static
    {
        return $this->state(fn ($attributes) => [
            'tenant_id' => $tenantId,
            'building_id' => $attributes['building_id']
                ?? \App\Models\Building::factory()->forTenantId($tenantId),
        ]);
    }

    /**
     * Ensure the property's building inherits the tenant when present.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Property $property) {
            $building = $property->building ?? \App\Models\Building::find($property->building_id);

            if ($building && $building->tenant_id !== $property->tenant_id) {
                $building->tenant_id = $property->tenant_id;
            }
        })->afterCreating(function (\App\Models\Property $property) {
            $building = $property->building ?? \App\Models\Building::find($property->building_id);

            if ($building && $building->tenant_id !== $property->tenant_id) {
                $building->update(['tenant_id' => $property->tenant_id]);
            }
        });
    }
}
