<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MeterReadingObserver
{
    /**
     * Handle the MeterReading "updating" event.
     * 
     * Creates an audit trail record when a meter reading is modified,
     * capturing the old value, new value, change reason, and user who made the change.
     * Also triggers recalculation of affected draft invoices.
     *
     * @param  \App\Models\MeterReading  $meterReading
     * @return void
     */
    public function updating(MeterReading $meterReading): void
    {
        // Only create audit record if the value is actually changing
        if ($meterReading->isDirty('value')) {
            MeterReadingAudit::create([
                'meter_reading_id' => $meterReading->id,
                'changed_by_user_id' => Auth::id() ?? $meterReading->entered_by ?? 1,
                'old_value' => $meterReading->getOriginal('value'),
                'new_value' => $meterReading->value,
                'change_reason' => $meterReading->change_reason ?? 'No reason provided',
            ]);
        }
    }

    /**
     * Handle the MeterReading "updated" event.
     * 
     * Recalculates affected draft invoices when a meter reading is modified.
     * Only draft invoices are recalculated; finalized invoices remain unchanged.
     *
     * @param  \App\Models\MeterReading  $meterReading
     * @return void
     */
    public function updated(MeterReading $meterReading): void
    {
        // Only recalculate if the value changed
        if ($meterReading->wasChanged('value')) {
            $this->recalculateAffectedDraftInvoices($meterReading);
        }
    }

    /**
     * Find and recalculate all draft invoices affected by a meter reading change.
     *
     * @param  \App\Models\MeterReading  $meterReading
     * @return void
     */
    private function recalculateAffectedDraftInvoices(MeterReading $meterReading): void
    {
        $meter = $meterReading->meter;

        if (!$meter) {
            return;
        }

        $draftInvoices = Invoice::query()
            ->draft()
            ->whereHas('tenant', fn ($q) => $q->where('property_id', $meter->property_id))
            ->whereDate('billing_period_start', '<=', $meterReading->reading_date)
            ->whereDate('billing_period_end', '>=', $meterReading->reading_date)
            ->get();

        // Recalculate each affected draft invoice
        $billingService = app(\App\Services\BillingService::class);

        foreach ($draftInvoices as $invoice) {
            try {
                $billingService->recalculateDraftInvoice($invoice);
            } catch (\Throwable $e) {
                Log::warning('Failed to recalculate draft invoice after meter reading update', [
                    'invoice_id' => $invoice->id,
                    'meter_reading_id' => $meterReading->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
