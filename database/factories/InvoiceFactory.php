<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'tenant_id' => null,
            'tenant_renter_id' => null,
            'billing_period_start' => $start,
            'billing_period_end' => $end,
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
        ];
    }

    /**
     * Attach to a specific renter tenant and sync tenant_id.
     */
    public function forTenantRenter(Tenant $tenant): static
    {
        return $this->state(fn ($attributes) => [
            'tenant_renter_id' => $tenant->id,
            'tenant_id' => $tenant->tenant_id,
        ]);
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
        return $this->state(function (array $attributes) {
            $paidAmount = $attributes['total_amount'] ?? fake()->randomFloat(2, 50, 500);

            return [
                'status' => InvoiceStatus::PAID,
                'finalized_at' => $attributes['finalized_at'] ?? now()->subDays(7),
                'paid_at' => now()->subDays(fake()->numberBetween(1, 14)),
                'payment_reference' => 'PAY-' . Str::upper(Str::random(8)),
                'paid_amount' => $paidAmount,
            ];
        });
    }

    /**
     * Keep tenant_id consistent with the renter relationship.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Invoice $invoice) {
            $tenant = $invoice->tenant ?? ($invoice->tenant_renter_id ? Tenant::find($invoice->tenant_renter_id) : null);

            if (! $tenant) {
                $tenant = Tenant::factory()
                    ->forTenantId($invoice->tenant_id ?? 1)
                    ->create();

                $invoice->tenant_renter_id = $tenant->id;
            }

            if ($invoice->tenant_id !== $tenant->tenant_id) {
                $invoice->tenant_id = $tenant->tenant_id;
            }
        })->afterCreating(function (Invoice $invoice) {
            $tenant = $invoice->tenant ?? ($invoice->tenant_renter_id ? Tenant::find($invoice->tenant_renter_id) : null);

            if (! $tenant) {
                $tenant = Tenant::factory()
                    ->forTenantId($invoice->tenant_id ?? 1)
                    ->create();

                $invoice->update(['tenant_renter_id' => $tenant->id]);
            }

            if ($tenant && $invoice->tenant_id !== $tenant->tenant_id) {
                $invoice->update(['tenant_id' => $tenant->tenant_id]);
            }
        });
    }
}
