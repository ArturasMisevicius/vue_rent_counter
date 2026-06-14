<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingIntegrity;

use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReminderLog;
use App\Models\MeterReading;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DetectBillingDuplicates
{
    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    public function forOrganization(int $organizationId): Collection
    {
        return collect()
            ->merge($this->duplicateActiveReadings($organizationId))
            ->merge($this->duplicateInvoices($organizationId))
            ->merge($this->duplicateInvoiceItems($organizationId))
            ->merge($this->duplicateExtraChargeInclusions($organizationId))
            ->merge($this->duplicateReminderLogs($organizationId));
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    public function forInvoice(Invoice $invoice): Collection
    {
        return collect()
            ->merge($this->duplicateActiveReadings((int) $invoice->organization_id, $invoice))
            ->merge($this->duplicateInvoices((int) $invoice->organization_id, $invoice))
            ->merge($this->duplicateInvoiceItems((int) $invoice->organization_id, $invoice))
            ->merge($this->duplicateExtraChargeInclusions((int) $invoice->organization_id, $invoice))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function duplicateActiveReadings(int $organizationId, ?Invoice $invoice = null): Collection
    {
        $query = MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_date',
                'validation_status',
            ])
            ->forOrganization($organizationId)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING,
                MeterReadingValidationStatus::VALID,
                MeterReadingValidationStatus::FLAGGED,
            ]);

        if ($invoice instanceof Invoice && $invoice->billing_period_start !== null && $invoice->billing_period_end !== null) {
            $query
                ->when($invoice->property_id !== null, fn (Builder $builder): Builder => $builder->where('property_id', $invoice->property_id))
                ->betweenDates($invoice->billing_period_start, $invoice->billing_period_end);
        }

        return $query
            ->get()
            ->groupBy(fn (MeterReading $reading): string => implode(':', [
                $reading->organization_id,
                $reading->property_id,
                $reading->meter_id,
                $reading->reading_date?->toDateString(),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->map(fn (Collection $group): BillingIntegrityIssue => new BillingIntegrityIssue(
                problemType: 'duplicate_active_readings',
                entityType: 'meter_reading',
                entityIds: $this->entityIds($group),
                severity: 'blocking',
                recommendedAction: 'keep_one_void_others',
                organizationId: $organizationId,
                context: [
                    'property_id' => $group->first()?->property_id,
                    'meter_id' => $group->first()?->meter_id,
                    'submitted_by_user_ids' => $group->pluck('submitted_by_user_id')->unique()->values()->all(),
                    'reading_date' => $group->first()?->reading_date?->toDateString(),
                ],
            ))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function duplicateInvoices(int $organizationId, ?Invoice $invoice = null): Collection
    {
        $query = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'billing_period_id',
                'property_id',
                'tenant_user_id',
                'billing_period_start',
                'billing_period_end',
                'status',
            ])
            ->forOrganization($organizationId)
            ->where('status', '!=', InvoiceStatus::VOID);

        if ($invoice instanceof Invoice && $invoice->billing_period_start !== null && $invoice->billing_period_end !== null) {
            $query
                ->when($invoice->property_id !== null, fn (Builder $builder): Builder => $builder->where('property_id', $invoice->property_id))
                ->when($invoice->tenant_user_id !== null, fn (Builder $builder): Builder => $builder->where('tenant_user_id', $invoice->tenant_user_id))
                ->when($invoice->billing_period_id !== null, fn (Builder $builder): Builder => $builder->where('billing_period_id', $invoice->billing_period_id))
                ->forBillingPeriod($invoice->billing_period_start, $invoice->billing_period_end);
        }

        return $query
            ->get()
            ->groupBy(fn (Invoice $candidate): string => implode(':', [
                $candidate->organization_id,
                $candidate->property_id,
                $candidate->tenant_user_id,
                $candidate->billing_period_id,
                $candidate->billing_period_start?->toDateString(),
                $candidate->billing_period_end?->toDateString(),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->map(fn (Collection $group): BillingIntegrityIssue => new BillingIntegrityIssue(
                problemType: 'duplicate_invoices',
                entityType: 'invoice',
                entityIds: $this->entityIds($group),
                severity: 'blocking',
                recommendedAction: 'cancel_duplicate_invoice',
                organizationId: $organizationId,
                context: [
                    'property_id' => $group->first()?->property_id,
                    'tenant_user_id' => $group->first()?->tenant_user_id,
                    'billing_period_id' => $group->first()?->billing_period_id,
                    'billing_period_start' => $group->first()?->billing_period_start?->toDateString(),
                    'billing_period_end' => $group->first()?->billing_period_end?->toDateString(),
                ],
            ))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function duplicateInvoiceItems(int $organizationId, ?Invoice $invoice = null): Collection
    {
        $query = InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id', 'service_configuration_id', 'utility_service_id', 'voided_at'])
            ->whereNull('voided_at')
            ->whereNotNull('source_type')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('source_id')
                    ->orWhereNotNull('service_configuration_id')
                    ->orWhereNotNull('utility_service_id');
            })
            ->whereHas('invoice', fn (Builder $builder): Builder => $builder->forOrganization($organizationId));

        if ($invoice instanceof Invoice) {
            $query->where('invoice_id', $invoice->id);
        }

        return $query
            ->get()
            ->groupBy(fn (InvoiceItem $item): string => implode(':', [
                $item->invoice_id,
                $item->source_type instanceof InvoiceItemSourceType ? $item->source_type->value : (string) $item->source_type,
                $item->source_id === null
                    ? 'service:'.$item->service_configuration_id.':'.$item->utility_service_id
                    : 'source:'.$item->source_id,
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->map(fn (Collection $group): BillingIntegrityIssue => new BillingIntegrityIssue(
                problemType: 'duplicate_invoice_items',
                entityType: 'invoice_item',
                entityIds: $this->entityIds($group),
                severity: 'blocking',
                recommendedAction: 'void_duplicate_invoice_item',
                organizationId: $organizationId,
                context: [
                    'invoice_id' => $group->first()?->invoice_id,
                    'source_type' => $group->first()?->source_type instanceof InvoiceItemSourceType
                        ? $group->first()?->source_type->value
                        : (string) $group->first()?->source_type,
                    'source_id' => $group->first()?->source_id,
                    'service_configuration_id' => $group->first()?->service_configuration_id,
                    'utility_service_id' => $group->first()?->utility_service_id,
                ],
            ))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function duplicateExtraChargeInclusions(int $organizationId, ?Invoice $invoice = null): Collection
    {
        $query = InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id', 'voided_at'])
            ->whereNull('voided_at')
            ->where('source_type', InvoiceItemSourceType::EXTRA_CHARGE)
            ->whereNotNull('source_id')
            ->whereHas('invoice', function (Builder $builder) use ($organizationId, $invoice): Builder {
                $builder->forOrganization($organizationId);

                if ($invoice instanceof Invoice && $invoice->billing_period_start !== null && $invoice->billing_period_end !== null) {
                    $builder->forBillingPeriod($invoice->billing_period_start, $invoice->billing_period_end);
                }

                return $builder;
            });

        if ($invoice instanceof Invoice) {
            $query->where('invoice_id', $invoice->id);
        }

        $itemsByCharge = $query->with(['invoice:id,organization_id,billing_period_start,billing_period_end'])
            ->get()
            ->groupBy('source_id')
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        return $itemsByCharge
            ->map(fn (Collection $group): BillingIntegrityIssue => new BillingIntegrityIssue(
                problemType: 'charges_included_twice',
                entityType: 'invoice_item',
                entityIds: $this->entityIds($group),
                severity: 'blocking',
                recommendedAction: 'void_duplicate_invoice_item',
                organizationId: $organizationId,
                context: [
                    'extra_charge_id' => $group->first()?->source_id,
                    'invoice_ids' => $group->pluck('invoice_id')->unique()->values()->all(),
                ],
            ))
            ->values();
    }

    /**
     * @return Collection<int, BillingIntegrityIssue>
     */
    private function duplicateReminderLogs(int $organizationId): Collection
    {
        return InvoiceReminderLog::query()
            ->select(['id', 'organization_id', 'invoice_id', 'recipient_email', 'channel', 'sent_at'])
            ->forOrganizationValue($organizationId)
            ->whereNotNull('sent_at')
            ->get()
            ->groupBy(fn (InvoiceReminderLog $log): string => implode(':', [
                $log->organization_id,
                $log->invoice_id,
                $log->recipient_email,
                $log->channel,
                $log->sent_at?->toDateString(),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->map(fn (Collection $group): BillingIntegrityIssue => new BillingIntegrityIssue(
                problemType: 'duplicate_reminders',
                entityType: 'invoice_reminder_log',
                entityIds: $this->entityIds($group),
                severity: 'warning',
                recommendedAction: 'archive_duplicate_reminders',
                organizationId: $organizationId,
                context: [
                    'invoice_id' => $group->first()?->invoice_id,
                    'recipient_email' => $group->first()?->recipient_email,
                    'channel' => $group->first()?->channel,
                    'sent_date' => $group->first()?->sent_at?->toDateString(),
                ],
            ))
            ->values();
    }

    /**
     * @param  Collection<int, object>  $records
     * @return list<int>
     */
    private function entityIds(Collection $records): array
    {
        return $records
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
