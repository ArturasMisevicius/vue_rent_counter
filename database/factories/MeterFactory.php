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

    /**
     * Attach the meter to a specific property and sync tenant_id.
     */
    public function forProperty(Property $property): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $property->id,
            'tenant_id' => $property->tenant_id,
        ]);
    }

    /**
     * Override tenant_id while keeping relationship factories aligned.
     */
    public function forTenantId(int $tenantId): static
    {
        return $this->state(function ($attributes) use ($tenantId) {
            $property = $attributes['property_id'] ?? Property::factory();

            if ($property instanceof Factory) {
                $property = $property->forTenantId($tenantId);
            }

            return [
                'tenant_id' => $tenantId,
                'property_id' => $property,
            ];
        });
    }

    /**
     * Ensure tenant_id follows the linked property.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Meter $meter) {
            $property = $meter->property ?? Property::find($meter->property_id);

            if ($property && $meter->tenant_id !== $property->tenant_id) {
                $meter->tenant_id = $property->tenant_id;
            }
        })->afterCreating(function (Meter $meter) {
            $property = $meter->property ?? Property::find($meter->property_id);

            if ($property && $meter->tenant_id !== $property->tenant_id) {
                $meter->update(['tenant_id' => $property->tenant_id]);
            }
        });
    }
}
