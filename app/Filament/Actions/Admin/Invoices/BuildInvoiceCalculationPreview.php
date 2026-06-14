<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\InvoiceItemSourceType;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Support\Admin\Invoices\InvoiceApprovalValidator;
use App\Filament\Support\Billing\InvoiceCalculationRows;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Invoice;

final class BuildInvoiceCalculationPreview
{
    public function __construct(
        private readonly InvoiceCalculationRows $calculationRows,
        private readonly InvoiceApprovalValidator $validator,
    ) {}

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>,
     *     can_approve: bool,
     *     has_warnings: bool
     * }
     */
    public function handle(Invoice $invoice): array
    {
        $rows = $this->calculationRows->forInvoice($invoice);
        $validation = $this->validator->validate($invoice);

        return [
            'items' => array_values(array_map(
                fn (array $row, int $index): array => $this->previewRow($invoice, $row, $index, $validation),
                $rows,
                array_keys($rows),
            )),
            'blocking_errors' => $validation['blocking_errors'],
            'warnings' => $validation['warnings'],
            'can_approve' => $validation['blocking_errors'] === [],
            'has_warnings' => $validation['warnings'] !== [],
        ];
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>,
     *     can_approve: bool,
     *     has_warnings: bool
     * }
     */
    public function __invoke(Invoice $invoice): array
    {
        return $this->handle($invoice);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     blocking_errors: array<int, array{message: string, item_index: int|null}>,
     *     warnings: array<int, array{message: string, item_index: int|null}>
     * }  $validation
     * @return array<string, mixed>
     */
    private function previewRow(Invoice $invoice, array $row, int $index, array $validation): array
    {
        $blockingErrors = $this->itemMessages($validation['blocking_errors'], $index);
        $warnings = $this->itemMessages($validation['warnings'], $index);

        return [
            'status' => $blockingErrors !== [] ? 'blocked' : ($warnings !== [] ? 'warning' : 'ready'),
            'status_label' => $blockingErrors !== []
                ? __('admin.invoices.preview.status.blocked')
                : ($warnings !== [] ? __('admin.invoices.preview.status.warning') : __('admin.invoices.preview.status.ready')),
            'title' => (string) ($row['title'] ?: $row['description_for_tenant'] ?: $row['description'] ?: __('admin.invoices.fields.items')),
            'source' => $this->sourceLabel($row),
            'source_url' => $this->sourceUrl($row),
            'formula' => (string) ($row['formula_label'] ?: __('admin.invoices.formulas.manual_amount')),
            'quantity' => trim((string) ($row['quantity'] ?? '').(filled($row['unit'] ?? null) ? ' '.(string) $row['unit'] : '')),
            'unit_price' => EuMoneyFormatter::format($row['unit_price'] ?? '0', (string) ($row['currency'] ?? $invoice->currency)),
            'subtotal' => EuMoneyFormatter::format($row['subtotal'] ?? $row['total'] ?? '0', (string) ($row['currency'] ?? $invoice->currency)),
            'tax' => EuMoneyFormatter::format($row['tax_amount'] ?? '0', (string) ($row['currency'] ?? $invoice->currency)),
            'total' => EuMoneyFormatter::format($row['total'] ?? '0', (string) ($row['currency'] ?? $invoice->currency)),
            'tenant_visible' => (bool) ($row['tenant_visible'] ?? true),
            'tenant_visibility_label' => (bool) ($row['tenant_visible'] ?? true)
                ? __('admin.invoices.preview.visible_to_tenant')
                : __('admin.invoices.preview.internal_only'),
            'blocking_errors' => $blockingErrors,
            'warnings' => $warnings,
            'description_for_tenant' => (string) ($row['description_for_tenant'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array{message: string, item_index: int|null}>  $issues
     * @return array<int, string>
     */
    private function itemMessages(array $issues, int $index): array
    {
        return array_values(array_map(
            fn (array $issue): string => $issue['message'],
            array_filter(
                $issues,
                fn (array $issue): bool => $issue['item_index'] === $index,
            ),
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceLabel(array $row): string
    {
        $label = (string) ($row['source_label'] ?? __('admin.invoices.source_types.unknown'));
        $sourceId = $row['source_id'] ?? null;

        if (! is_numeric($sourceId)) {
            return $label;
        }

        return "{$label} #{$sourceId}";
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceUrl(array $row): ?string
    {
        $sourceType = InvoiceItemSourceType::tryFrom((string) ($row['source_type'] ?? ''));
        $sourceId = $row['source_id'] ?? null;

        if (! is_numeric($sourceId) || ! $sourceType instanceof InvoiceItemSourceType) {
            return null;
        }

        return match ($sourceType) {
            InvoiceItemSourceType::METER_READING => MeterReadingResource::getUrl('view', ['record' => (int) $sourceId]),
            InvoiceItemSourceType::FIXED_SERVICE => ServiceConfigurationResource::getUrl('view', ['record' => (int) $sourceId]),
            default => null,
        };
    }
}
