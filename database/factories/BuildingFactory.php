<?php

namespace Database\Factories;

use App\Filament\Support\Geography\BalticReferenceCatalog;
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
        $city = fake()->randomElement(BalticReferenceCatalog::cities());

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->streetName().' Residence',
            'address_line_1' => fake()->streetName().' '.fake()->buildingNumber(),
            'address_line_2' => null,
            'city' => $city['name'],
            'postal_code' => $this->postalCodeFor($city['postal_code_pattern']),
            'country_code' => $city['country_code'],
        ];
    }

    public function named(string $name): static
    {
        return $this->state([
            'name' => $name,
        ]);
    }

    /**
     * @param array{name: string, country_code: string, postal_code_pattern: string} $city
     */
    public function atBalticAddress(array $city, string $street, ?string $addressLine2 = null, ?string $postalCode = null): static
    {
        return $this->state([
            'address_line_1' => $street,
            'address_line_2' => $addressLine2,
            'city' => $city['name'],
            'postal_code' => $postalCode ?? $this->postalCodeFor($city['postal_code_pattern']),
            'country_code' => $city['country_code'],
        ]);
    }

    private function postalCodeFor(string $pattern): string
    {
        return (string) str($pattern)->replaceMatches('/#/', fn (): string => (string) fake()->numberBetween(0, 9));
    }
}
