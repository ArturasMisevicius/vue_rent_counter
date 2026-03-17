<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceGenerationAudit>
 */
class InvoiceGenerationAuditFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);

        return [
            'invoice_id' => Invoice::factory()
                ->for($organization)
                ->for(Property::factory()->for($organization)->for(Building::factory()->for($organization)))
                ->for($tenant, 'tenant'),
            'organization_id' => $organization,
            'tenant_user_id' => $tenant,
            'user_id' => User::factory()->admin()->for($organization),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'total_amount' => fake()->randomFloat(2, 25, 500),
            'items_count' => fake()->numberBetween(1, 8),
            'metadata' => [
                'source' => 'factory',
            ],
            'execution_time_ms' => fake()->randomFloat(2, 20, 450),
            'query_count' => fake()->numberBetween(3, 25),
            'created_at' => now(),
        ];
    }
}
