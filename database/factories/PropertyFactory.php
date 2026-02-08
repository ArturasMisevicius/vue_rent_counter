<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
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
            'address' => $this->faker->streetAddress(),
            'type' => $this->faker->randomElement(['apartment', 'house']),
            'area_sqm' => $this->faker->randomFloat(2, 30, 200),
            'unit_number' => $this->faker->optional()->bothify('##?'),
            'building_id' => null, // Will be set by relationships if needed
        ];
    }

    /**
     * Set the tenant_id for this property.
     */
    public function forTenantId(int $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Indicate that the property is an apartment.
     */
    public function apartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'apartment',
            'area_sqm' => $this->faker->randomFloat(2, 30, 120),
        ]);
    }

    /**
     * Indicate that the property is a house.
     */
    public function house(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'house',
            'area_sqm' => $this->faker->randomFloat(2, 80, 300),
        ]);
    }
}