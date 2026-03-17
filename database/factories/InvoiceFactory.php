<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $property = Property::factory()->for($organization)->for(Building::factory()->for($organization));

        return [
            'organization_id' => $organization,
            'property_id' => $property,
            'tenant_user_id' => User::factory()->tenant()->for($organization),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'status' => InvoiceStatus::FINALIZED,
            'currency' => 'EUR',
            'total_amount' => fake()->randomFloat(2, 25, 500),
            'amount_paid' => 0,
            'due_date' => now()->addDays(14)->toDateString(),
            'finalized_at' => now(),
            'paid_at' => null,
            'document_path' => null,
            'notes' => null,
        ];
    }
}
