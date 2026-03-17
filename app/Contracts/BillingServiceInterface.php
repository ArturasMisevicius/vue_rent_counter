<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\DistributionMethod;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

interface BillingServiceInterface
{
    /**
     * @param  array{billing_period_start: string, billing_period_end: string}  $attributes
     * @return array{valid: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    public function previewBulkInvoices(Organization $organization, array $attributes): array;

    /**
     * @param  array{billing_period_start: string, billing_period_end: string, due_date?: string}  $attributes
     * @return array{created: Collection<int, Invoice>, skipped: array<int, array{tenant_id: int, property_id: int, reason: string}>}
     */
    public function generateBulkInvoices(Organization $organization, array $attributes, ?User $actor = null): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function saveDraft(Invoice $invoice, array $attributes): Invoice;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function finalize(Invoice $invoice, array $attributes = []): Invoice;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function applyPayment(Invoice $invoice, array $attributes, ?User $actor = null): Invoice;

    public function calculateFlatRateCharge(string|int|float $quantity, string|int|float $unitRate, string|int|float $baseFee = '0'): string;

    /**
     * @param  array<string, string|int|float>  $zoneConsumptions
     * @param  array<int, array{id?: string, rate?: string|int|float}>  $zones
     */
    public function calculateTimeOfUseCharge(array $zoneConsumptions, array $zones, string|int|float $baseFee = '0'): string;

    /**
     * @param  array<string, mixed>  $context
     */
    public function distributeSharedServiceCost(
        string|int|float $totalCost,
        DistributionMethod $distributionMethod,
        array $context = [],
    ): string;
}
