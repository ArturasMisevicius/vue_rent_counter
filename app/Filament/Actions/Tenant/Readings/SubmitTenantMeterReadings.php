<?php

declare(strict_types=1);

namespace App\Filament\Actions\Tenant\Readings;

use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\User;

final readonly class SubmitTenantMeterReadings
{
    public function __construct(
        private SubmitTenantReadingAction $submitTenantReading,
        private CompleteReadingRequestInvoiceAction $completeReadingRequestInvoice,
    ) {}

    /**
     * @param  array<int|string, array{meter_id?: int|string, reading_value?: string|int|float, value?: string|int|float, reading_date?: string, notes?: string|null}>  $readings
     * @return list<MeterReading>
     */
    public function handle(User $tenant, Invoice $invoice, array $readings): array
    {
        $submitted = [];

        foreach ($readings as $meterId => $reading) {
            $submitted[] = $this->submitTenantReading->handle(
                tenant: $tenant,
                meterId: $reading['meter_id'] ?? $meterId,
                readingValue: $reading['reading_value'] ?? $reading['value'] ?? '',
                readingDate: (string) ($reading['reading_date'] ?? now()->toDateString()),
                notes: $reading['notes'] ?? null,
                invoiceId: $invoice->id,
            );
        }

        $this->completeReadingRequestInvoice->handle($tenant, $invoice->id, $submitted);

        return $submitted;
    }
}
