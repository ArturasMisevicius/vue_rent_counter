<?php

declare(strict_types=1);

namespace App\Filament\Support\Billing;

use App\Enums\InvoiceItemSourceType;
use App\Models\Invoice;
use App\Models\InvoiceItem;

final class InvoiceCalculationRows
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forInvoice(Invoice $invoice, bool $tenantVisibleOnly = false): array
    {
        $invoice->loadMissing([
            'invoiceItems' => fn ($query) => $query
                ->select([
                    'id',
                    'invoice_id',
                    'source_type',
                    'source_id',
                    'service_configuration_id',
                    'utility_service_id',
                    'tariff_id',
                    'provider_id',
                    'title',
                    'description',
                    'description_for_tenant',
                    'internal_note',
                    'quantity',
                    'unit',
                    'unit_price',
                    'subtotal',
                    'tax_amount',
                    'discount_amount',
                    'total',
                    'currency',
                    'formula_label',
                    'calculation_snapshot',
                    'tenant_visible',
                    'sort_order',
                    'meter_reading_snapshot',
                    'service_snapshot',
                    'tariff_snapshot',
                    'provider_snapshot',
                ])
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $rows = $this->rowsFromSnapshot($invoice->snapshot_data);

        if ($rows === [] && $invoice->invoiceItems->isNotEmpty()) {
            $rows = $invoice->invoiceItems
                ->map(fn (InvoiceItem $item): array => $this->rowFromInvoiceItem($item, (string) $invoice->currency))
                ->all();
        }

        if ($rows === []) {
            $rows = $this->rowsFromSnapshot($invoice->items);
        }

        $rows = array_values(array_map(
            fn (array $row, int $index): array => $this->normalizeRow($row, (string) $invoice->currency, $index),
            $rows,
            array_keys($rows),
        ));

        if (! $tenantVisibleOnly) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => (bool) ($row['tenant_visible'] ?? true),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsFromSnapshot(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $rows = is_array($items['items'] ?? null) ? $items['items'] : $items;

        return array_values(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && $this->looksLikeInvoiceItem($row),
        ));
    }

    private function looksLikeInvoiceItem(array $row): bool
    {
        return array_key_exists('description', $row)
            || array_key_exists('description_for_tenant', $row)
            || array_key_exists('total', $row)
            || array_key_exists('amount', $row);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowFromInvoiceItem(InvoiceItem $item, string $invoiceCurrency): array
    {
        $sourceType = $item->source_type instanceof InvoiceItemSourceType
            ? $item->source_type->value
            : $item->source_type;

        return [
            'source_type' => $sourceType,
            'source_id' => $item->source_id,
            'service_configuration_id' => $item->service_configuration_id,
            'utility_service_id' => $item->utility_service_id,
            'tariff_id' => $item->tariff_id,
            'provider_id' => $item->provider_id,
            'title' => $item->title,
            'description' => $item->description,
            'description_for_tenant' => $item->description_for_tenant,
            'internal_note' => $item->internal_note,
            'quantity' => (string) $item->quantity,
            'unit' => $item->unit,
            'unit_price' => (string) $item->unit_price,
            'subtotal' => (string) ($item->subtotal ?? $item->total),
            'tax_amount' => (string) ($item->tax_amount ?? '0'),
            'discount_amount' => (string) ($item->discount_amount ?? '0'),
            'total' => (string) $item->total,
            'currency' => $item->currency ?: $invoiceCurrency,
            'formula_label' => $item->formula_label,
            'calculation_snapshot' => $item->calculation_snapshot,
            'tenant_visible' => $item->tenant_visible,
            'sort_order' => $item->sort_order,
            'is_adjustment' => false,
            'meter_reading_snapshot' => $item->meter_reading_snapshot,
            'service_snapshot' => $item->service_snapshot,
            'tariff_snapshot' => $item->tariff_snapshot,
            'provider_snapshot' => $item->provider_snapshot,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, string $invoiceCurrency, int $index): array
    {
        $sourceType = InvoiceItemSourceType::tryFrom((string) ($row['source_type'] ?? ''));
        $description = (string) ($row['description'] ?? $row['description_for_tenant'] ?? $row['title'] ?? '');
        $tenantDescription = (string) ($row['description_for_tenant'] ?? $description);
        $total = (string) ($row['total'] ?? $row['amount'] ?? '0');
        $unitPrice = (string) ($row['unit_price'] ?? $row['rate'] ?? $row['amount'] ?? $total);
        $calculationSnapshot = is_array($row['calculation_snapshot'] ?? null)
            ? $row['calculation_snapshot']
            : [];
        $meterReadingSnapshot = is_array($row['meter_reading_snapshot'] ?? null)
            ? $row['meter_reading_snapshot']
            : data_get($calculationSnapshot, 'meter_reading_snapshot');

        return [
            'source_type' => $sourceType?->value ?? (string) ($row['source_type'] ?? ''),
            'source_label' => $sourceType?->label() ?? __('admin.invoices.source_types.unknown'),
            'source_id' => is_numeric($row['source_id'] ?? null) ? (int) $row['source_id'] : null,
            'service_configuration_id' => is_numeric($row['service_configuration_id'] ?? null) ? (int) $row['service_configuration_id'] : null,
            'utility_service_id' => is_numeric($row['utility_service_id'] ?? null) ? (int) $row['utility_service_id'] : null,
            'tariff_id' => is_numeric($row['tariff_id'] ?? null) ? (int) $row['tariff_id'] : null,
            'provider_id' => is_numeric($row['provider_id'] ?? null) ? (int) $row['provider_id'] : null,
            'title' => (string) ($row['title'] ?? $description),
            'description' => $description,
            'description_for_tenant' => $tenantDescription,
            'internal_note' => $row['internal_note'] ?? null,
            'quantity' => (string) ($row['quantity'] ?? '1'),
            'unit' => (string) ($row['unit'] ?? ''),
            'unit_price' => $unitPrice,
            'subtotal' => (string) ($row['subtotal'] ?? $total),
            'tax_amount' => (string) ($row['tax_amount'] ?? '0'),
            'discount_amount' => (string) ($row['discount_amount'] ?? '0'),
            'total' => $total,
            'currency' => (string) ($row['currency'] ?? $invoiceCurrency),
            'formula_label' => (string) ($row['formula_label'] ?? data_get($calculationSnapshot, 'formula_label', '')),
            'calculation_snapshot' => $calculationSnapshot,
            'tenant_visible' => array_key_exists('tenant_visible', $row) ? (bool) $row['tenant_visible'] : true,
            'sort_order' => (int) ($row['sort_order'] ?? $index + 1),
            'is_adjustment' => (bool) ($row['is_adjustment'] ?? false),
            'meter_reading_snapshot' => is_array($meterReadingSnapshot) ? $meterReadingSnapshot : null,
            'service_snapshot' => is_array($row['service_snapshot'] ?? null)
                ? $row['service_snapshot']
                : data_get($calculationSnapshot, 'service_snapshot'),
            'tariff_snapshot' => is_array($row['tariff_snapshot'] ?? null)
                ? $row['tariff_snapshot']
                : data_get($calculationSnapshot, 'tariff_snapshot'),
            'provider_snapshot' => is_array($row['provider_snapshot'] ?? null)
                ? $row['provider_snapshot']
                : data_get($calculationSnapshot, 'provider_snapshot'),
        ];
    }
}
