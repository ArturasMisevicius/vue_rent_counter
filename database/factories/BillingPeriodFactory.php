<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfMonth();

        return [
            'organization_id' => Organization::factory(),
            'name' => $start->format('F Y'),
            'starts_at' => $start->toDateString(),
            'ends_at' => $start->copy()->endOfMonth()->toDateString(),
            'reading_submission_deadline' => $start->copy()->endOfMonth()->addDays(14)->toDateString(),
            'invoice_generation_date' => now()->toDateString(),
            'payment_due_date' => $start->copy()->endOfMonth()->addDays(28)->toDateString(),
        ];
    }
}
