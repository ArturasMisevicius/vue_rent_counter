<?php

namespace Database\Factories;

use App\Enums\ServiceType;
use App\Models\Organization;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    public function definition(): array
    {
        $serviceType = fake()->randomElement(ServiceType::cases());

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'service_type' => $serviceType,
            'contact_info' => [
                'phone' => fake()->phoneNumber(),
                'email' => fake()->companyEmail(),
                'website' => fake()->url(),
            ],
        ];
    }

    public function global(): static
    {
        return $this->state(fn () => [
            'organization_id' => null,
        ]);
    }

    public function forOrganization(?Organization $organization = null): static
    {
        return $this->state(fn () => [
            'organization_id' => $organization?->id ?? Organization::factory(),
        ]);
    }

    public function ignitis(): static
    {
        return $this->state(fn () => [
            'name' => 'Ignitis',
            'service_type' => ServiceType::ELECTRICITY,
        ]);
    }

    public function vilniausVandenys(): static
    {
        return $this->state(fn () => [
            'name' => 'Vilniaus Vandenys',
            'service_type' => ServiceType::WATER,
        ]);
    }

    public function vilniausEnergija(): static
    {
        return $this->state(fn () => [
            'name' => 'Vilniaus Energija',
            'service_type' => ServiceType::HEATING,
        ]);
    }
}
