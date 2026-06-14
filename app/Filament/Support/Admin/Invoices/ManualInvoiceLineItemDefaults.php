<?php

namespace App\Filament\Support\Admin\Invoices;

use DateTimeInterface;

class ManualInvoiceLineItemDefaults
{
    /**
     * @param  array<string, mixed>  $invoiceState
     * @return array<string, string|null>
     */
    public function make(array $invoiceState): array
    {
        return [
            'description' => '',
            'period' => $this->periodLabel($invoiceState),
            'unit' => 'service',
            'quantity' => '1.00',
            'rate' => '0.00',
            'total' => '0.00',
        ];
    }

    /**
     * @param  array<string, mixed>  $invoiceState
     */
    private function periodLabel(array $invoiceState): ?string
    {
        $start = $invoiceState['billing_period_start'] ?? null;
        $end = $invoiceState['billing_period_end'] ?? null;

        $start = $this->normalizeDateValue($start);
        $end = $this->normalizeDateValue($end);

        if ($start === null || $end === null) {
            return null;
        }

        return sprintf('%s - %s', $start, $end);
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }
}
