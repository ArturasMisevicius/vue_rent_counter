<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class InvoiceSummary extends Component
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly bool $showPropertyFilter = false,
        public readonly mixed $properties = [],
        public readonly mixed $consumptionHistory = null,
    ) {}

    public function isOverdue(Invoice $invoice): bool
    {
        if (! $invoice->due_date) {
            return false;
        }

        return method_exists($invoice, 'isOverdue')
            ? (bool) $invoice->isOverdue()
            : (! $invoice->isPaid() && $invoice->due_date->isPast());
    }

    public function readingSummary(mixed $snapshot, bool $short = false): ?string
    {
        if (! is_array($snapshot)) {
            return null;
        }

        if (isset($snapshot['meters']) && is_array($snapshot['meters']) && count($snapshot['meters']) > 0) {
            return $this->multiMeterSummary($snapshot, $short);
        }

        if (isset($snapshot['previous_reading'], $snapshot['current_reading'])) {
            return $this->singleMeterSummary(
                $snapshot['previous_reading'],
                $snapshot['current_reading'],
                $short
            );
        }

        return null;
    }

    public function render(): View
    {
        return view('components.invoice-summary');
    }

    private function multiMeterSummary(array $snapshot, bool $short): ?string
    {
        $firstMeter = $snapshot['meters'][0] ?? null;
        if (! is_array($firstMeter)) {
            return null;
        }

        $start = $firstMeter['start_value'] ?? $firstMeter['previous_reading'] ?? null;
        $end = $firstMeter['end_value'] ?? $firstMeter['current_reading'] ?? null;
        if ($start === null || $end === null) {
            return null;
        }

        $startFormatted = $this->formatReadingValue($start);
        $endFormatted = $this->formatReadingValue($end);
        $previousLabel = $short ? __('invoices.summary.labels.prev_short') : __('invoices.summary.labels.previous');
        $currentLabel = $short ? __('invoices.summary.labels.curr_short') : __('invoices.summary.labels.current');

        if ($short) {
            return "{$previousLabel}: {$startFormatted} -> {$currentLabel}: {$endFormatted}";
        }

        $meterCount = count($snapshot['meters']);
        $serial = $firstMeter['meter_serial'] ?? null;
        $zone = $snapshot['zone'] ?? ($firstMeter['zone'] ?? null);
        $meterLabel = $serial ? "{$serial}: " : '';
        $zoneLabel = $zone ? " ({$zone})" : '';
        $moreLabel = $meterCount > 1 ? ' +' . ($meterCount - 1) . ' more' : '';

        return $meterLabel . "{$previousLabel}: {$startFormatted} -> {$currentLabel}: {$endFormatted}{$zoneLabel}{$moreLabel}";
    }

    private function singleMeterSummary(mixed $previous, mixed $current, bool $short): string
    {
        $previousLabel = $short ? __('invoices.summary.labels.prev_short') : __('invoices.summary.labels.previous');
        $currentLabel = $short ? __('invoices.summary.labels.curr_short') : __('invoices.summary.labels.current');

        return "{$previousLabel}: {$this->formatReadingValue($previous)} -> {$currentLabel}: {$this->formatReadingValue($current)}";
    }

    private function formatReadingValue(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 2) : (string) $value;
    }
}
