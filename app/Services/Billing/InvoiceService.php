<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Actions\Billing\CreateManualPayment;
use App\Enums\AuditLogAction;
use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Events\InvoiceFinalized;
use App\Filament\Support\Admin\ExtraCharges\ExtraChargeInvoiceIntegrator;
use App\Filament\Support\Admin\Invoices\InvoiceApprovalValidator;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InvoiceService
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
        private readonly DashboardCacheService $dashboardCacheService,
        private readonly AuditLogger $auditLogger,
        private readonly InvoiceApprovalValidator $invoiceApprovalValidator,
        private readonly ExtraChargeInvoiceIntegrator $extraChargeInvoiceIntegrator,
        private readonly CreateManualPayment $createManualPayment,
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

            if ($normalizedItems !== null) {
                $payload['items'] = $normalizedItems;
                $payload['snapshot_data'] = $normalizedItems;
                $payload['snapshot_created_at'] = now();
                $payload['total_amount'] = $payload['total_amount'] ?? $this->sumLineItems($normalizedItems);
            }

            $invoice->update($payload);

            if ($normalizedItems !== null) {
                $this->syncInvoiceItems($invoice, $normalizedItems);
            }

            return $invoice->fresh(['invoiceItems', 'payments']);
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createDraft(
        Organization $organization,
        PropertyAssignment $assignment,
        array $validated,
        ?User $actor = null,
    ): Invoice {
        return DB::transaction(function () use ($organization, $assignment, $validated, $actor): Invoice {
            $items = $this->normalizeLineItems(
                $this->mergeAdjustmentItems(
                    is_array($validated['items'] ?? null) ? $validated['items'] : [],
                    is_array($validated['adjustments'] ?? null) ? $validated['adjustments'] : [],
                ),
            );
            $totalAmount = $this->sumLineItems($items);
            $billingPeriodStart = CarbonImmutable::parse((string) $validated['billing_period_start'])->toDateString();
            $billingPeriodEnd = CarbonImmutable::parse((string) $validated['billing_period_end'])->toDateString();
            $dueDate = filled($validated['due_date'] ?? null)
                ? CarbonImmutable::parse((string) $validated['due_date'])->toDateString()
                : CarbonImmutable::parse($billingPeriodEnd)->addDays(14)->toDateString();
            $invoice = Invoice::query()->create([
                'organization_id' => $organization->id,
                'property_id' => $assignment->property_id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'property_assignment_id' => $assignment->id,
                'invoice_number' => 'INV-TEMP-'.Str::uuid(),
                'billing_period_start' => $billingPeriodStart,
                'billing_period_end' => $billingPeriodEnd,
                'status' => InvoiceStatus::DRAFT,
                'payment_status' => InvoicePaymentStatus::UNPAID,
                'currency' => 'EUR',
                'total_amount' => $totalAmount,
                'amount_paid' => $this->calculator->money('0'),
                'paid_amount' => $this->calculator->money('0'),
                'balance_amount' => $totalAmount,
                'due_date' => $dueDate,
                'items' => $items,
                'snapshot_data' => $items,
                'snapshot_created_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->update([
                'invoice_number' => $this->formattedInvoiceNumber($invoice),
            ]);

            $this->syncInvoiceItems($invoice, $items);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments']);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $freshInvoice,
                [
                    'workspace' => $this->workspaceContext($freshInvoice),
                    'context' => [
                        'mutation' => 'invoice.created',
                    ],
                    'after' => $this->invoiceAuditSnapshot($freshInvoice),
                ],
                $actor?->id,
                'Invoice created',
            );

            DB::afterCommit(function () use ($organization): void {
                $this->dashboardCacheService->touchOrganization($organization->id);
            });

            return $freshInvoice;
        });
    }

    public function createReadingRequestDraft(
        Organization $organization,
        PropertyAssignment $assignment,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
        string $dueDate,
        ?User $actor = null,
        ?BillingPeriod $billingPeriod = null,
        array $readingRequestSnapshot = [],
    ): Invoice {
        return DB::transaction(function () use (
            $organization,
            $assignment,
            $billingPeriodStart,
            $billingPeriodEnd,
            $dueDate,
            $actor,
            $billingPeriod,
            $readingRequestSnapshot,
        ): Invoice {
            $invoice = Invoice::query()->create([
                'organization_id' => $organization->id,
                'billing_period_id' => $billingPeriod?->id,
                'property_id' => $assignment->property_id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'property_assignment_id' => $assignment->id,
                'invoice_number' => 'INV-TEMP-'.Str::uuid(),
                'billing_period_start' => $billingPeriodStart->toDateString(),
                'billing_period_end' => $billingPeriodEnd->toDateString(),
                'status' => InvoiceStatus::DRAFT,
                'payment_status' => InvoicePaymentStatus::UNPAID,
                'currency' => 'EUR',
                'total_amount' => $this->calculator->money('0'),
                'amount_paid' => $this->calculator->money('0'),
                'paid_amount' => $this->calculator->money('0'),
                'balance_amount' => $this->calculator->money('0'),
                'due_date' => $dueDate,
                'items' => [],
                'snapshot_data' => [],
                'snapshot_created_at' => now(),
                'generated_at' => now(),
                'generated_by' => $actor !== null ? "user:{$actor->id}" : 'billing:reading-invoice-cycle',
                'approval_status' => 'waiting_for_readings',
                'automation_level' => 'reading_request',
                'approval_metadata' => [
                    'workflow' => 'meter_reading_request',
                    'source' => 'billing:open-reading-invoice-cycle',
                    'request_status' => 'waiting_for_readings',
                    'billing_period_id' => $billingPeriod?->id,
                    'reading_submission_deadline' => $billingPeriod?->reading_submission_deadline?->toDateString() ?? $dueDate,
                    'invoice_generation_date' => $billingPeriod?->invoice_generation_date?->toDateString(),
                    'payment_due_date' => $billingPeriod?->payment_due_date?->toDateString(),
                    'tenant' => $readingRequestSnapshot['tenant'] ?? [
                        'id' => $assignment->tenant_user_id,
                        'name' => (string) ($assignment->tenant?->name ?? ''),
                    ],
                    'property' => $readingRequestSnapshot['property'] ?? [
                        'id' => $assignment->property_id,
                        'name' => (string) ($assignment->property?->displayName() ?? ''),
                    ],
                    'period' => $readingRequestSnapshot['period'] ?? [
                        'id' => $billingPeriod?->id,
                        'name' => (string) ($billingPeriod?->name ?? ''),
                        'starts_at' => $billingPeriodStart->toDateString(),
                        'ends_at' => $billingPeriodEnd->toDateString(),
                    ],
                    'linked_meters' => $readingRequestSnapshot['linked_meters'] ?? [],
                    'expected_services' => $readingRequestSnapshot['expected_services'] ?? [],
                    'required_inputs' => $readingRequestSnapshot['required_inputs'] ?? [],
                ],
                'notes' => __('admin.invoices.reading_request.invoice_note'),
            ]);

            $invoice->update([
                'invoice_number' => $this->formattedInvoiceNumber($invoice),
            ]);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments']);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $freshInvoice,
                [
                    'workspace' => $this->workspaceContext($freshInvoice),
                    'context' => [
                        'mutation' => 'invoice.reading_request_opened',
                    ],
                    'after' => $this->invoiceAuditSnapshot($freshInvoice),
                ],
                $actor?->id,
                'Invoice opened for meter reading request',
            );

            DB::afterCommit(function () use ($organization): void {
                $this->dashboardCacheService->touchOrganization($organization->id);
            });

            return $freshInvoice;
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
                'property_assignment_id' => $assignment->id,
                'invoice_number' => sprintf(
                    'INV-%s-%d-%d',
                    $billingPeriodStart->format('Ym'),
                    $assignment->property_id,
                    $assignment->tenant_user_id,
                ),
                'billing_period_start' => $billingPeriodStart->toDateString(),
                'billing_period_end' => $billingPeriodEnd->toDateString(),
                'status' => InvoiceStatus::FINALIZED,
                'payment_status' => InvoicePaymentStatus::UNPAID,
                'currency' => 'EUR',
                'total_amount' => $totalAmount,
                'amount_paid' => $this->calculator->money('0'),
                'paid_amount' => $this->calculator->money('0'),
                'balance_amount' => $totalAmount,
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
            $this->extraChargeInvoiceIntegrator->markIncluded($invoice, $items, $actor?->id);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments', 'billingRecords']);
            $this->invoiceApprovalValidator->ensureCanApprove($freshInvoice);

            InvoiceGenerationAudit::query()->create([
                'invoice_id' => $freshInvoice->id,
                'organization_id' => $organization->id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'user_id' => $actor?->id,
                'period_start' => $billingPeriodStart->toDateString(),
                'period_end' => $billingPeriodEnd->toDateString(),
                'total_amount' => $freshInvoice->total_amount,
                'items_count' => count($items),
                'metadata' => [
                    'workspace' => $this->workspaceContext($freshInvoice),
                    'context' => [
                        'mutation' => 'invoice.generated',
                    ],
                    'invoice_number' => $freshInvoice->invoice_number,
                ],
                'created_at' => now(),
            ]);

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

    /**
     * @param  array{items: array<int, array<string, mixed>>, total_amount: string|int|float}  $lineItemPayload
     */
    public function prepareReadingRequestDraft(
        Invoice $invoice,
        PropertyAssignment $assignment,
        array $lineItemPayload,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
        ?User $actor = null,
    ): Invoice {
        return DB::transaction(function () use (
            $invoice,
            $assignment,
            $lineItemPayload,
            $billingPeriodStart,
            $billingPeriodEnd,
            $actor,
        ): Invoice {
            $before = $this->invoiceAuditSnapshot($invoice);
            $items = $this->normalizeLineItems($lineItemPayload['items']);
            $totalAmount = $this->calculator->money(
                $lineItemPayload['total_amount'] ?? $this->sumLineItems($items),
            );
            $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];

            $invoice->forceFill([
                'total_amount' => $totalAmount,
                'balance_amount' => $totalAmount,
                'payment_status' => InvoicePaymentStatus::UNPAID,
                'items' => $items,
                'snapshot_data' => $items,
                'snapshot_created_at' => now(),
                'approval_status' => 'ready_for_review',
                'approval_metadata' => [
                    ...$metadata,
                    'workflow' => $metadata['workflow'] ?? 'meter_reading_request',
                    'prepared_from_readings_at' => now()->toISOString(),
                    'prepared_by_user_id' => $actor?->id,
                    'prepared_invoice_item_count' => count($items),
                ],
            ])->save();

            $this->syncInvoiceItems($invoice, $items);
            $this->syncBillingRecords($invoice, $assignment, $items, $billingPeriodStart, $billingPeriodEnd);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments', 'billingRecords']);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshInvoice,
                [
                    'workspace' => $this->workspaceContext($freshInvoice),
                    'context' => [
                        'mutation' => 'invoice.reading_request_prepared',
                    ],
                    'before' => $before,
                    'after' => $this->invoiceAuditSnapshot($freshInvoice),
                ],
                $actor?->id,
                'Invoice prepared from submitted meter readings',
            );

            DB::afterCommit(function () use ($freshInvoice): void {
                $this->dashboardCacheService->touchOrganization($freshInvoice->organization_id);
            });

            return $freshInvoice;
        });
    }

    /**
     * @param  array<string, mixed>|null  $beforeSnapshot
     */
    public function markAsFinalized(Invoice $invoice, ?User $actor = null, ?array $beforeSnapshot = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor, $beforeSnapshot): Invoice {
            $before = $beforeSnapshot ?? $this->invoiceAuditSnapshot($invoice);
            $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];

            $invoice->update([
                'status' => InvoiceStatus::FINALIZED,
                'finalized_at' => $invoice->finalized_at ?? now(),
                'approval_status' => 'approved',
                'approval_metadata' => [
                    ...$metadata,
                    'approved_from_status' => $invoice->approval_status,
                    'approved_at' => now()->toISOString(),
                ],
                'approved_by' => $actor?->id ?? $invoice->approved_by,
                'approved_at' => $invoice->approved_at ?? now(),
            ]);
            $this->extraChargeInvoiceIntegrator->markIncluded($invoice, $invoice->items, $actor?->id);

            $freshInvoice = $invoice->fresh(['invoiceItems', 'payments']);

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $freshInvoice,
                [
                    'workspace' => $this->workspaceContext($freshInvoice),
                    'context' => [
                        'mutation' => 'invoice.finalized',
                    ],
                    'before' => $before,
                    'after' => $this->invoiceAuditSnapshot($freshInvoice),
                ],
                $actor?->id,
                'Invoice finalized',
            );

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
        $paymentAmount = $this->calculator->money(
            $validated['amount_paid'] ?? $validated['paid_amount'] ?? $invoice->amount_paid,
        );
        $paidAt = $validated['paid_at'] ?? now();
        $method = $validated['payment_method'] ?? $validated['method'] ?? PaymentMethod::OTHER;

        $payment = $this->createManualPayment->handle($invoice, $actor, [
            'amount' => $paymentAmount,
            'payment_method' => $method,
            'payment_date' => is_object($paidAt) && method_exists($paidAt, 'toDateString')
                ? $paidAt->toDateString()
                : (string) $paidAt,
            'reference' => $validated['payment_reference'] ?? null,
            'internal_note' => $validated['notes'] ?? null,
            'confirm_immediately' => true,
        ]);

        $freshInvoice = $payment->invoice?->fresh(['payments']) ?? $invoice->fresh(['payments']);

        DB::afterCommit(function () use ($freshInvoice): void {
            $this->dashboardCacheService->touchOrganization($freshInvoice->organization_id);
        });

        return $freshInvoice;
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
     * @return array<string, mixed>
     */
    private function invoiceAuditSnapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status instanceof InvoiceStatus
                ? $invoice->status->value
                : $invoice->status,
            'total_amount' => $this->normalizeNumericSnapshotValue($invoice->total_amount),
            'amount_paid' => $this->normalizeNumericSnapshotValue($invoice->amount_paid),
            'paid_amount' => $this->normalizeNumericSnapshotValue($invoice->paid_amount),
            'payment_reference' => $invoice->payment_reference,
            'finalized_at' => $invoice->finalized_at?->toISOString(),
            'paid_at' => $invoice->paid_at?->toISOString(),
        ];
    }

    /**
     * @return array{organization_id: int, property_id: int|null, tenant_user_id: int|null}
     */
    private function workspaceContext(Invoice $invoice): array
    {
        return [
            'organization_id' => $invoice->organization_id,
            'property_id' => $invoice->property_id,
            'tenant_user_id' => $invoice->tenant_user_id,
        ];
    }

    private function normalizeNumericSnapshotValue(string|int|float|null $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numericValue = (float) $value;

        if ((float) (int) $numericValue === $numericValue) {
            return (int) $numericValue;
        }

        return $numericValue;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLineItems(array $items): array
    {
        $normalized = [];

        foreach (array_values($items) as $index => $item) {
            $resolvedItem = is_array($item) ? $item : [];
            $total = $resolvedItem['total'] ?? $resolvedItem['amount'] ?? 0;
            $unitPrice = $resolvedItem['unit_price'] ?? $resolvedItem['rate'] ?? $total;
            $quantity = $resolvedItem['quantity'] ?? 1;
            $description = (string) ($resolvedItem['description'] ?? $resolvedItem['description_for_tenant'] ?? $resolvedItem['title'] ?? '');
            $sourceType = $this->resolveSourceType($resolvedItem, $total);
            $sourceId = $this->resolveSourceId($resolvedItem, $sourceType);
            $subtotal = $this->calculator->money($resolvedItem['subtotal'] ?? $total);
            $taxAmount = $this->calculator->money($resolvedItem['tax_amount'] ?? 0);
            $discountAmount = $this->calculator->money($resolvedItem['discount_amount'] ?? 0);
            $normalizedQuantity = $this->calculator->quantity($quantity);
            $normalizedUnitPrice = $this->calculator->rate($unitPrice);
            $normalizedTotal = $this->calculator->money($total);
            $currency = (string) ($resolvedItem['currency'] ?? 'EUR');
            $formulaLabel = (string) ($resolvedItem['formula_label'] ?? __('admin.invoices.formulas.quantity_times_unit_price'));
            $tenantVisible = array_key_exists('tenant_visible', $resolvedItem)
                ? (bool) $resolvedItem['tenant_visible']
                : true;
            $meterReadingSnapshot = $resolvedItem['meter_reading_snapshot'] ?? null;
            $serviceSnapshot = $resolvedItem['service_snapshot'] ?? null;
            $tariffSnapshot = $resolvedItem['tariff_snapshot'] ?? null;
            $providerSnapshot = $resolvedItem['provider_snapshot'] ?? null;

            $normalized[] = [
                'source_type' => $sourceType->value,
                'source_id' => $sourceId,
                'service_configuration_id' => $resolvedItem['service_configuration_id'] ?? null,
                'utility_service_id' => $resolvedItem['utility_service_id'] ?? null,
                'tariff_id' => $resolvedItem['tariff_id'] ?? null,
                'provider_id' => $resolvedItem['provider_id'] ?? null,
                'title' => (string) ($resolvedItem['title'] ?? $description),
                'description' => $description,
                'description_for_tenant' => (string) ($resolvedItem['description_for_tenant'] ?? $description),
                'internal_note' => $resolvedItem['internal_note'] ?? null,
                'period' => filled($resolvedItem['period'] ?? null)
                    ? (string) $resolvedItem['period']
                    : null,
                'quantity' => $normalizedQuantity,
                'unit' => $resolvedItem['unit'] ?? null,
                'unit_price' => $normalizedUnitPrice,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $normalizedTotal,
                'currency' => $currency,
                'formula_label' => $formulaLabel,
                'calculation_snapshot' => $this->resolveCalculationSnapshot(
                    resolvedItem: $resolvedItem,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    quantity: $normalizedQuantity,
                    unitPrice: $normalizedUnitPrice,
                    subtotal: $subtotal,
                    taxAmount: $taxAmount,
                    discountAmount: $discountAmount,
                    total: $normalizedTotal,
                    currency: $currency,
                    formulaLabel: $formulaLabel,
                    meterReadingSnapshot: is_array($meterReadingSnapshot) ? $meterReadingSnapshot : null,
                    serviceSnapshot: is_array($serviceSnapshot) ? $serviceSnapshot : null,
                    tariffSnapshot: is_array($tariffSnapshot) ? $tariffSnapshot : null,
                    providerSnapshot: is_array($providerSnapshot) ? $providerSnapshot : null,
                ),
                'tenant_visible' => $tenantVisible,
                'sort_order' => (int) ($resolvedItem['sort_order'] ?? $index + 1),
                'consumption' => $this->calculator->quantity($resolvedItem['consumption'] ?? $quantity),
                'rate' => $this->calculator->rate($resolvedItem['rate'] ?? $unitPrice),
                'is_adjustment' => (bool) ($resolvedItem['is_adjustment'] ?? false),
                'meter_reading_snapshot' => $meterReadingSnapshot,
                'service_snapshot' => $serviceSnapshot,
                'tariff_snapshot' => $tariffSnapshot,
                'provider_snapshot' => $providerSnapshot,
                'billable' => (bool) ($resolvedItem['billable'] ?? true),
            ];
        }

        return $normalized;
    }

    private function resolveSourceType(array $item, string|int|float $total): InvoiceItemSourceType
    {
        $sourceType = InvoiceItemSourceType::tryFrom((string) ($item['source_type'] ?? ''));

        if ($sourceType instanceof InvoiceItemSourceType) {
            return $sourceType;
        }

        if (is_array($item['meter_reading_snapshot'] ?? null)) {
            return InvoiceItemSourceType::METER_READING;
        }

        if (! empty($item['utility_service_id'])) {
            return InvoiceItemSourceType::FIXED_SERVICE;
        }

        if ((bool) ($item['is_adjustment'] ?? false)) {
            return ((float) $total) < 0
                ? InvoiceItemSourceType::DISCOUNT
                : InvoiceItemSourceType::CORRECTION;
        }

        if (((float) $total) < 0) {
            return InvoiceItemSourceType::DISCOUNT;
        }

        return InvoiceItemSourceType::EXTRA_CHARGE;
    }

    private function resolveSourceId(array $item, InvoiceItemSourceType $sourceType): ?int
    {
        if (is_numeric($item['source_id'] ?? null)) {
            return (int) $item['source_id'];
        }

        if ($sourceType === InvoiceItemSourceType::METER_READING) {
            $endReadingId = data_get($item, 'meter_reading_snapshot.end.id');

            return is_numeric($endReadingId) ? (int) $endReadingId : null;
        }

        if ($sourceType === InvoiceItemSourceType::FIXED_SERVICE && is_numeric($item['service_configuration_id'] ?? null)) {
            return (int) $item['service_configuration_id'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resolvedItem
     * @param  array<string, mixed>|null  $meterReadingSnapshot
     * @param  array<string, mixed>|null  $serviceSnapshot
     * @param  array<string, mixed>|null  $tariffSnapshot
     * @param  array<string, mixed>|null  $providerSnapshot
     * @return array<string, mixed>
     */
    private function resolveCalculationSnapshot(
        array $resolvedItem,
        InvoiceItemSourceType $sourceType,
        ?int $sourceId,
        string $quantity,
        string $unitPrice,
        string $subtotal,
        string $taxAmount,
        string $discountAmount,
        string $total,
        string $currency,
        string $formulaLabel,
        ?array $meterReadingSnapshot,
        ?array $serviceSnapshot,
        ?array $tariffSnapshot,
        ?array $providerSnapshot,
    ): array {
        if (is_array($resolvedItem['calculation_snapshot'] ?? null)) {
            return $resolvedItem['calculation_snapshot'];
        }

        return [
            'source_type' => $sourceType->value,
            'source_id' => $sourceId,
            'source_status' => (string) ($resolvedItem['source_status'] ?? 'approved'),
            'formula_label' => $formulaLabel,
            'quantity' => $quantity,
            'unit' => $resolvedItem['unit'] ?? null,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'currency' => $currency,
            'meter_reading_snapshot' => $meterReadingSnapshot,
            'service_snapshot' => $serviceSnapshot,
            'tariff_snapshot' => $tariffSnapshot,
            'provider_snapshot' => $providerSnapshot,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<int, array<string, mixed>>
     */
    private function mergeAdjustmentItems(array $items, array $adjustments): array
    {
        $normalizedAdjustments = collect($adjustments)
            ->filter(function (mixed $adjustment): bool {
                if (! is_array($adjustment)) {
                    return false;
                }

                return filled($adjustment['label'] ?? null)
                    || filled($adjustment['amount'] ?? null);
            })
            ->map(function (array $adjustment): array {
                $amount = $adjustment['amount'] ?? 0;
                $description = (string) ($adjustment['label'] ?? __('admin.invoices.fields.adjustment'));

                return [
                    'source_type' => ((float) $amount) < 0
                        ? InvoiceItemSourceType::DISCOUNT->value
                        : InvoiceItemSourceType::CORRECTION->value,
                    'source_status' => 'approved',
                    'title' => $description,
                    'description' => $description,
                    'description_for_tenant' => $description,
                    'period' => null,
                    'quantity' => '1',
                    'unit' => null,
                    'unit_price' => $amount,
                    'subtotal' => $amount,
                    'tax_amount' => '0',
                    'discount_amount' => '0',
                    'rate' => $amount,
                    'total' => $amount,
                    'currency' => 'EUR',
                    'formula_label' => __('admin.invoices.formulas.manual_amount'),
                    'consumption' => '1',
                    'is_adjustment' => true,
                    'meter_reading_snapshot' => null,
                ];
            })
            ->values()
            ->all();

        return [...$items, ...$normalizedAdjustments];
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
            'source_type' => $item['source_type'],
            'source_id' => $item['source_id'],
            'service_configuration_id' => $item['service_configuration_id'],
            'utility_service_id' => $item['utility_service_id'],
            'tariff_id' => $item['tariff_id'],
            'provider_id' => $item['provider_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'description_for_tenant' => $item['description_for_tenant'],
            'internal_note' => $item['internal_note'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
            'tax_amount' => $item['tax_amount'],
            'discount_amount' => $item['discount_amount'],
            'total' => $item['total'],
            'currency' => $item['currency'],
            'formula_label' => $item['formula_label'],
            'calculation_snapshot' => $item['calculation_snapshot'],
            'tenant_visible' => $item['tenant_visible'],
            'sort_order' => $item['sort_order'],
            'meter_reading_snapshot' => $item['meter_reading_snapshot'],
            'service_snapshot' => $item['service_snapshot'],
            'tariff_snapshot' => $item['tariff_snapshot'],
            'provider_snapshot' => $item['provider_snapshot'],
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
        return $this->calculator->sumMoney(
            array_map(
                fn (array $item): string|int|float => $item['total'] ?? $item['amount'] ?? 0,
                $items,
            ),
        );
    }

    private function formattedInvoiceNumber(Invoice $invoice): string
    {
        return sprintf('INV-%s-%04d', now()->format('Y'), $invoice->id);
    }
}
