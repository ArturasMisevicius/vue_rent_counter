<?php

namespace Database\Factories;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meter>
 */
class MeterFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(MeterType::cases());
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'property_id' => Property::factory()->for($organization),
            'name' => fake()->randomElement(['Main Meter', 'Kitchen Meter', 'Heating Meter']),
            'identifier' => strtoupper(fake()->bothify('MTR-####-??')),
            'type' => $type,
            'status' => MeterStatus::ACTIVE,
            'unit' => $type->defaultUnit()->value,
            'installed_at' => now()->subYear()->toDateString(),
        ];
    }

    public function identified(string $identifier, MeterType $type, string $name, string $installedAt): static
    {
        return $this->state([
            'identifier' => $identifier,
            'type' => $type,
            'name' => $name,
            'unit' => $type->defaultUnit()->value,
            'installed_at' => $installedAt,
        ]);
    }
}
