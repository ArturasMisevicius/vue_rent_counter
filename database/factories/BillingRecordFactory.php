<?php

namespace Database\Factories;

use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRecord>
 */
class BillingRecordFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $property = Property::factory()->for($organization)->for(Building::factory()->for($organization));
        $consumption = fake()->randomFloat(3, 5, 400);
        $rate = fake()->randomFloat(4, 0.05, 2.50);

        return [
            'organization_id' => $organization,
            'property_id' => $property,
            'utility_service_id' => UtilityService::factory()->for($organization),
            'invoice_id' => Invoice::factory()->for($organization)->for($property),
            'tenant_user_id' => User::factory()->tenant()->for($organization),
            'amount' => round($consumption * $rate, 2),
            'consumption' => $consumption,
            'rate' => $rate,
            'meter_reading_start' => null,
            'meter_reading_end' => null,
            'notes' => null,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
        ];
    }
}
