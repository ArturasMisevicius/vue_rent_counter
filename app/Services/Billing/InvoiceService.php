<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Events\InvoiceFinalized;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class InvoiceService
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly DashboardCacheService $dashboardCacheService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateDraft(Invoice $invoice, array $validated): Invoice
    {
        return DB::transaction(function () use ($invoice, $validated): Invoice {
            $payload = $this->normalizeDraftPayload($validated);
            $normalizedItems = null;

            if (array_key_exists('items', $payload)) {
                $normalizedItems = $this->normalizeLineItems(is_array($payload['items']) ? $payload['items'] : []);
            }

            $invoice->update($payload);

            if ($normalizedItems !== null) {
                $this->syncInvoiceItems($invoice, $normalizedItems);
            }

            return $invoice->fresh(['invoiceItems', 'payments']);
        });
    }

    /**
     * @param  array{items: array<int, array<string, mixed>>, total_amount: string|int|float}  $lineItemPayload
     */
    public function createGeneratedInvoice(
        Organization $organization,
        PropertyAssignment $assignment,
        array $lineItemPayload,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
        string $dueDate,
        ?User $actor = null,
    ): Invoice {
        return DB::transaction(function () use (
            $organization,
            $assignment,
            $lineItemPayload,
            $billingPeriodStart,
            $billingPeriodEnd,
            $dueDate,
            $actor,
        ): Invoice {
            $items = $this->normalizeLineItems($lineItemPayload['items']);
            $totalAmount = $this->calculator->money(
                $lineItemPayload['total_amount'] ?? $this->sumLineItems($items),
            );

            $invoice = Invoice::query()->create([
                'organization_id' => $organization->id,
                'property_id' => $assignment->property_id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'invoice_number' => sprintf(
                    'INV-%s-%d-%d',
                    $billingPeriodStart->format('Ym'),
                    $assignment->property_id,
                    $assignment->tenant_user_id,
                ),
                'billing_period_start' => $billingPeriodStart->toDateString(),
                'billing_period_end' => $billingPeriodEnd->toDateString(),
                'status' => InvoiceStatus::FINALIZED,
                'currency' => 'EUR',
                'total_amount' => $totalAmount,
                'amount_paid' => $this->calculator->money('0'),
                'paid_amount' => $this->calculator->money('0'),
                'due_date' => $dueDate,
                'finalized_at' => now(),
                'items' => $items,
                'snapshot_data' => $items,
                'snapshot_created_at' => now(),
                'generated_at' => now(),
                'generated_by' => $actor !== null ? "user:{$actor->id}" : 'billing_service',
                'approval_status' => 'approved',
                'automation_level' => 'service',
                'approved_by' => $actor?->id,
                'approved_at' => $actor !== null ? now() : null,
            ]);

            $this->syncInvoiceItems($invoice, $items);
            $this->syncBillingRecords($invoice, $assignment, $items, $billingPeriodStart, $billingPeriodEnd);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments', 'billingRecords']);

            DB::afterCommit(function () use ($organization, $freshInvoice, $assignment): void {
                $this->dashboardCacheService->touchOrganization($organization->id);

                event(new InvoiceFinalized(
                    organizationId: $organization->id,
                    invoiceId: $freshInvoice->id,
                    tenantUserId: $assignment->tenant_user_id,
                ));
            });

            return $freshInvoice;
        });
    }

    public function markAsFinalized(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->update([
                'status' => InvoiceStatus::FINALIZED,
                'finalized_at' => $invoice->finalized_at ?? now(),
            ]);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments']);

            DB::afterCommit(function () use ($freshInvoice): void {
                $this->dashboardCacheService->touchOrganization($freshInvoice->organization_id);

                event(new InvoiceFinalized(
                    organizationId: $freshInvoice->organization_id,
                    invoiceId: $freshInvoice->id,
                    tenantUserId: $freshInvoice->tenant_user_id,
                ));
            });

            return $freshInvoice;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function recordPayment(Invoice $invoice, array $validated, ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $validated, $actor): Invoice {
            $paymentAmount = $this->calculator->money(
                $validated['amount_paid'] ?? $validated['paid_amount'] ?? $invoice->amount_paid,
            );
            $newPaidAmount = $this->calculator->money(
                $this->calculator->add($invoice->normalized_paid_amount, $paymentAmount, 6),
            );
            $status = $this->calculator->compare($newPaidAmount, $invoice->total_amount, 2) >= 0
                ? InvoiceStatus::PAID
                : InvoiceStatus::PARTIALLY_PAID;
            $paidAt = $validated['paid_at'] ?? now();

            $invoice->update([
                'amount_paid' => $newPaidAmount,
                'paid_amount' => $newPaidAmount,
                'payment_reference' => $validated['payment_reference'] ?? $invoice->payment_reference,
                'paid_at' => $paidAt,
                'status' => $status,
            ]);

            InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'organization_id' => $invoice->organization_id,
                'recorded_by_user_id' => $actor?->id,
                'amount' => $paymentAmount,
                'method' => $validated['method'] ?? PaymentMethod::OTHER,
                'reference' => $validated['payment_reference'] ?? null,
                'paid_at' => $paidAt,
                'notes' => $validated['notes'] ?? null,
            ]);

            $freshInvoice = $invoice->fresh(['payments']);

            DB::afterCommit(function () use ($freshInvoice): void {
                $this->dashboardCacheService->touchOrganization($freshInvoice->organization_id);
            });

            return $freshInvoice;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeDraftPayload(array $payload): array
    {
        $normalized = Arr::only($payload, [
            'invoice_number',
            'billing_period_start',
            'billing_period_end',
            'status',
            'total_amount',
            'amount_paid',
            'paid_amount',
            'due_date',
            'paid_at',
            'payment_reference',
            'items',
            'notes',
        ]);

        if (array_key_exists('items', $normalized)) {
            $normalized['items'] = array_values(array_map(function (mixed $item): array {
                $resolvedItem = is_array($item) ? $item : [];

                if (array_key_exists('amount', $resolvedItem)) {
                    $resolvedItem['amount'] = $this->calculator->money($resolvedItem['amount']);
                }

                return $resolvedItem;
            }, is_array($normalized['items']) ? $normalized['items'] : []));

            if (! array_key_exists('total_amount', $normalized)) {
                $normalized['total_amount'] = $this->sumLineItems($normalized['items']);
            }
        }

        if (array_key_exists('total_amount', $normalized)) {
            $normalized['total_amount'] = $this->calculator->money($normalized['total_amount']);
        }

        if (array_key_exists('amount_paid', $normalized)) {
            $normalized['amount_paid'] = $this->calculator->money($normalized['amount_paid']);
        }

        if (array_key_exists('paid_amount', $normalized)) {
            $normalized['paid_amount'] = $this->calculator->money($normalized['paid_amount']);
        }

        if (array_key_exists('amount_paid', $normalized) && ! array_key_exists('paid_amount', $normalized)) {
            $normalized['paid_amount'] = $normalized['amount_paid'];
        }

        if (array_key_exists('paid_amount', $normalized) && ! array_key_exists('amount_paid', $normalized)) {
            $normalized['amount_paid'] = $normalized['paid_amount'];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLineItems(array $items): array
    {
        return array_values(array_map(function (mixed $item): array {
            $resolvedItem = is_array($item) ? $item : [];
            $total = $resolvedItem['total'] ?? $resolvedItem['amount'] ?? 0;
            $unitPrice = $resolvedItem['unit_price'] ?? $resolvedItem['rate'] ?? $total;
            $quantity = $resolvedItem['quantity'] ?? 1;

            return [
                'utility_service_id' => $resolvedItem['utility_service_id'] ?? null,
                'description' => (string) ($resolvedItem['description'] ?? ''),
                'quantity' => $this->calculator->quantity($quantity),
                'unit' => $resolvedItem['unit'] ?? null,
                'unit_price' => $this->calculator->rate($unitPrice),
                'total' => $this->calculator->money($total),
                'consumption' => $this->calculator->quantity($resolvedItem['consumption'] ?? $quantity),
                'rate' => $this->calculator->rate($resolvedItem['rate'] ?? $unitPrice),
                'meter_reading_snapshot' => $resolvedItem['meter_reading_snapshot'] ?? null,
            ];
        }, $items));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncInvoiceItems(Invoice $invoice, array $items): void
    {
        $invoice->invoiceItems()->delete();

        if ($items === []) {
            return;
        }

        $invoice->invoiceItems()->createMany(array_map(fn (array $item): array => [
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'unit_price' => $item['unit_price'],
            'total' => $item['total'],
            'meter_reading_snapshot' => $item['meter_reading_snapshot'],
        ], $items));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncBillingRecords(
        Invoice $invoice,
        PropertyAssignment $assignment,
        array $items,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
    ): void {
        $invoice->billingRecords()->delete();

        $records = collect($items)
            ->filter(fn (array $item): bool => ! empty($item['utility_service_id']))
            ->map(function (array $item) use ($invoice, $billingPeriodStart, $billingPeriodEnd): array {
                /** @var array<string, mixed>|null $snapshot */
                $snapshot = is_array($item['meter_reading_snapshot'] ?? null) ? $item['meter_reading_snapshot'] : null;

                return [
                    'organization_id' => $invoice->organization_id,
                    'property_id' => $invoice->property_id,
                    'utility_service_id' => $item['utility_service_id'],
                    'tenant_user_id' => $invoice->tenant_user_id,
                    'amount' => $item['total'],
                    'consumption' => $item['consumption'],
                    'rate' => $item['rate'],
                    'meter_reading_start' => $snapshot['start']['id'] ?? null,
                    'meter_reading_end' => $snapshot['end']['id'] ?? null,
                    'billing_period_start' => $billingPeriodStart->toDateString(),
                    'billing_period_end' => $billingPeriodEnd->toDateString(),
                    'notes' => $item['description'],
                ];
            })
            ->all();

        if ($records !== []) {
            $invoice->billingRecords()->createMany($records);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function sumLineItems(array $items): string
    {
        return $this->calculator->money(
            $this->calculator->sum(
                array_map(
                    fn (array $item): string|int|float => $item['total'] ?? $item['amount'] ?? 0,
                    $items,
                ),
                6,
            ),
        );
    }
}
