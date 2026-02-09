<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    public function definition(): array
    {
        $propertyId = Property::query()->where('tenant_id', 1)->value('id')
            ?? Property::factory()->forTenantId(1)->create()->id;

        $renterId = Tenant::query()->where('tenant_id', 1)->where('property_id', $propertyId)->value('id')
            ?? Tenant::factory()->forTenantId(1)->create(['property_id' => $propertyId])->id;

        $startDate = fake()->dateTimeBetween('-1 year', '-2 months');
        $endDate = (clone $startDate)->modify('+1 year');

        return [
            'tenant_id' => 1,
            'property_id' => $propertyId,
            'renter_id' => $renterId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => fake()->randomFloat(2, 350, 1250),
            'deposit' => fake()->randomFloat(2, 600, 2200),
            'is_active' => true,
        ];
    }
}
