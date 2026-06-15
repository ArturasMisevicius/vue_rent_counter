<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BillingGenerationLog;
use App\Models\BillingGenerationLogItem;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingGenerationLogItem>
 */
class BillingGenerationLogItemFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'billing_generation_log_id' => BillingGenerationLog::factory()->for($organization),
            'organization_id' => $organization,
            'billing_period_id' => null,
            'invoice_id' => null,
            'property_assignment_id' => null,
            'tenant_user_id' => null,
            'property_id' => null,
            'level' => 'info',
            'code' => 'generated',
            'message' => 'Generation item recorded.',
            'context' => [],
        ];
    }
}
