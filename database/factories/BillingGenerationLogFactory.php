<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BillingGenerationLog;
use App\Models\BillingPeriod;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingGenerationLog>
 */
class BillingGenerationLogFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $start = now()->subMonthNoOverflow()->startOfMonth();

        return [
            'organization_id' => $organization,
            'billing_period_id' => BillingPeriod::factory()->for($organization),
            'initiated_by_user_id' => User::factory()->admin()->for($organization),
            'source' => 'manual',
            'status' => 'completed',
            'dry_run' => false,
            'billing_period_start' => $start->toDateString(),
            'billing_period_end' => $start->copy()->endOfMonth()->toDateString(),
            'invoice_generation_date' => now()->toDateString(),
            'reading_submission_deadline' => now()->addDays(5)->toDateString(),
            'payment_due_date' => now()->addDays(19)->toDateString(),
            'eligible_count' => 0,
            'created_count' => 0,
            'skipped_count' => 0,
            'warning_count' => 0,
            'error_count' => 0,
            'notified_tenants_count' => 0,
            'summary' => [],
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }
}
