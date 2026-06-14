<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\Invoices;

use App\Enums\InvoiceItemSourceType;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Support\Billing\InvoiceCalculationRows;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Validation\ValidationException;

final class InvoiceApprovalValidator
{
    public function __construct(
        private readonly InvoiceCalculationRows $calculationRows,
    ) {}

    /**
     * @return array{
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>
     * }
     */
    public function validate(Invoice $invoice): array
    {
        $rows = $this->calculationRows->forInvoice($invoice);
        $blockingErrors = [];
        $warnings = [];

        if ($rows === []) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.no_items'));
        }

        foreach ($rows as $index => $row) {
            $this->validateRow($invoice, $row, $index, $blockingErrors, $warnings);
        }

        if ($this->shouldCheckReadings($invoice, $rows)) {
            $this->validateCurrentReadings($invoice, $blockingErrors);
        }

        return [
            'blocking_errors' => $blockingErrors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>
     * }
     */
    public function ensureCanApprove(Invoice $invoice, bool $allowWarnings = false): array
    {
        $result = $this->validate($invoice);

        if ($result['blocking_errors'] !== []) {
            throw ValidationException::withMessages([
                'invoice' => array_column($result['blocking_errors'], 'message'),
            ]);
        }

        if (! $allowWarnings && $result['warnings'] !== []) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.validation.warnings_need_confirmation'),
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, array{message: string, item_index: int|null}>  $blockingErrors
     * @param  array<int, array{message: string, item_index: int|null}>  $warnings
     */
    private function validateRow(
        Invoice $invoice,
        array $row,
        int $index,
        array &$blockingErrors,
        array &$warnings,
    ): void {
        $sourceType = InvoiceItemSourceType::tryFrom((string) ($row['source_type'] ?? ''));
        $calculationSnapshot = is_array($row['calculation_snapshot'] ?? null) ? $row['calculation_snapshot'] : [];
        $meterSnapshot = is_array($row['meter_reading_snapshot'] ?? null)
            ? $row['meter_reading_snapshot']
            : data_get($calculationSnapshot, 'meter_reading_snapshot');

        if (! $sourceType instanceof InvoiceItemSourceType) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.source_required'), $index);
        }

        if (! is_numeric($row['total'] ?? null)) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.empty_amount'), $index);
        }

        if ((string) ($row['currency'] ?? $invoice->currency) !== (string) $invoice->currency) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.currency_mismatch'), $index);
        }

        if ((bool) ($row['tenant_visible'] ?? true) && trim((string) ($row['description_for_tenant'] ?? '')) === '') {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.tenant_description_required'), $index);
        }

        if ($sourceType instanceof InvoiceItemSourceType && $this->requiresTariff($sourceType)) {
            $tariffId = $row['tariff_id'] ?? data_get($row, 'tariff_snapshot.id') ?? data_get($calculationSnapshot, 'tariff_snapshot.id');

            if (! is_numeric($tariffId)) {
                $blockingErrors[] = $this->issue(__('admin.invoices.validation.tariff_missing'), $index);
            }
        }

        $sourceStatus = (string) data_get($calculationSnapshot, 'source_status', 'approved');

        if (in_array($sourceStatus, ['pending', 'rejected'], true)) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.source_charge_unapproved'), $index);
        }

        if (is_array($meterSnapshot)) {
            $this->validateMeterSnapshot($meterSnapshot, $index, $blockingErrors, $warnings);
        }
    }

    /**
     * @param  array<string, mixed>  $meterSnapshot
     * @param  array<int, array{message: string, item_index: int|null}>  $blockingErrors
     * @param  array<int, array{message: string, item_index: int|null}>  $warnings
     */
    private function validateMeterSnapshot(
        array $meterSnapshot,
        int $index,
        array &$blockingErrors,
        array &$warnings,
    ): void {
        foreach (['start', 'end'] as $side) {
            $status = (string) data_get($meterSnapshot, "{$side}.validation_status", '');

            if ($status === MeterReadingValidationStatus::PENDING->value) {
                $blockingErrors[] = $this->issue(__('admin.invoices.validation.unapproved_readings'), $index);
            }

            if ($status === MeterReadingValidationStatus::REJECTED->value) {
                $blockingErrors[] = $this->issue(__('admin.invoices.validation.rejected_readings'), $index);
            }

            if ($status === MeterReadingValidationStatus::FLAGGED->value) {
                $warnings[] = $this->issue(__('admin.invoices.validation.flagged_readings_warning'), $index);
            }
        }

        if (
            (bool) ($meterSnapshot['negative_consumption'] ?? false)
            && ! (bool) ($meterSnapshot['negative_consumption_confirmed'] ?? false)
        ) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.negative_consumption'), $index);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function shouldCheckReadings(Invoice $invoice, array $rows): bool
    {
        if ($invoice->automation_level === 'reading_request') {
            return true;
        }

        return collect($rows)->contains(
            fn (array $row): bool => ($row['source_type'] ?? null) === InvoiceItemSourceType::METER_READING->value,
        );
    }

    /**
     * @param  array<int, array{message: string, item_index: int|null}>  $blockingErrors
     */
    private function validateCurrentReadings(Invoice $invoice, array &$blockingErrors): void
    {
        if ($invoice->property_id === null || $invoice->billing_period_start === null || $invoice->billing_period_end === null) {
            return;
        }

        $statuses = MeterReading::query()
            ->select(['id', 'validation_status'])
            ->where('organization_id', $invoice->organization_id)
            ->where('property_id', $invoice->property_id)
            ->betweenDates($invoice->billing_period_start, $invoice->billing_period_end)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING->value,
                MeterReadingValidationStatus::REJECTED->value,
            ])
            ->get()
            ->pluck('validation_status')
            ->map(fn (MeterReadingValidationStatus|string $status): string => $status instanceof MeterReadingValidationStatus ? $status->value : (string) $status)
            ->all();

        if (in_array(MeterReadingValidationStatus::PENDING->value, $statuses, true)) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.unapproved_readings'));
        }

        if (in_array(MeterReadingValidationStatus::REJECTED->value, $statuses, true)) {
            $blockingErrors[] = $this->issue(__('admin.invoices.validation.rejected_readings'));
        }
    }

    private function requiresTariff(InvoiceItemSourceType $sourceType): bool
    {
        return in_array($sourceType, [
            InvoiceItemSourceType::METER_READING,
            InvoiceItemSourceType::FIXED_SERVICE,
        ], true);
    }

    /**
     * @return array{message: string, item_index: int|null}
     */
    private function issue(string $message, ?int $itemIndex = null): array
    {
        return [
            'message' => $message,
            'item_index' => $itemIndex,
        ];
    }
}
