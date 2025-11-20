<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');
        $end = (clone $start)->modify('+1 month');

        return [
            'tenant_id' => 1,
            'tenant_renter_id' => Tenant::factory(),
            'billing_period_start' => $start,
            'billing_period_end' => $end,
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::PAID,
            'finalized_at' => now()->subDays(7),
        ]);
    }
}
