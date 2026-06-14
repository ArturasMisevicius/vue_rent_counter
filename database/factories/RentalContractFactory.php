<?php

namespace Database\Factories;

use App\Enums\RentalContractStatus;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalContract>
 */
class RentalContractFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);
        $property = Property::factory()->for($organization);

        return [
            'organization_id' => $organization,
            'tenant_id' => $tenant,
            'property_id' => $property,
            'property_assignment_id' => PropertyAssignment::factory()
                ->for($organization)
                ->for($property)
                ->for($tenant, 'tenant'),
            'contract_number' => 'RC-'.fake()->unique()->numerify('######'),
            'status' => RentalContractStatus::ACTIVE,
            'start_date' => today(),
            'end_date' => today()->addYear(),
            'signed_date' => today(),
            'rent_amount' => fake()->randomFloat(2, 350, 2500),
            'deposit_amount' => fake()->randomFloat(2, 350, 2500),
            'currency' => 'EUR',
            'tenant_visible' => true,
            'internal_notes' => null,
            'tenant_visible_notes' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => RentalContractStatus::DRAFT,
            'tenant_visible' => false,
            'signed_date' => null,
        ]);
    }

    public function hiddenFromTenant(): static
    {
        return $this->state(fn (): array => [
            'tenant_visible' => false,
        ]);
    }
}
