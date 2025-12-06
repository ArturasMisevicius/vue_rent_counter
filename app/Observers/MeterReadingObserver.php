<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
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
        // Find all invoice items that reference this meter reading in their snapshot
        $affectedItems = InvoiceItem::whereJsonContains('meter_reading_snapshot->start_reading_id', $meterReading->id)
            ->orWhereJsonContains('meter_reading_snapshot->end_reading_id', $meterReading->id)
            ->get();

        // Get unique draft invoices from affected items
        $affectedInvoiceIds = $affectedItems->pluck('invoice_id')->unique();
        
        $draftInvoices = Invoice::whereIn('id', $affectedInvoiceIds)
            ->draft()
            ->get();

        // Recalculate each affected draft invoice
        foreach ($draftInvoices as $invoice) {
            $this->recalculateInvoice($invoice);
        }
    }

    /**
     * Recalculate an invoice's totals based on current meter readings.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    private function recalculateInvoice(Invoice $invoice): void
    {
        $totalAmount = 0;

        foreach ($invoice->items as $item) {
            $snapshot = $item->meter_reading_snapshot;
            
            if (!$snapshot) {
                continue;
            }

            // Get current meter reading values
            $startReading = MeterReading::find($snapshot['start_reading_id']);
            $endReading = MeterReading::find($snapshot['end_reading_id']);

            if (!$startReading || !$endReading) {
                continue;
            }

            // Recalculate consumption with current values
            $newConsumption = $endReading->value - $startReading->value;
            
            // Update item with new consumption and total
            $newTotal = $newConsumption * $item->unit_price;
            
            $item->update([
                'quantity' => $newConsumption,
                'total' => $newTotal,
                'meter_reading_snapshot' => array_merge($snapshot, [
                    'start_value' => $startReading->value,
                    'end_value' => $endReading->value,
                ]),
            ]);

            $totalAmount += $newTotal;
        }

        // Update invoice total
        $invoice->update(['total_amount' => $totalAmount]);
    }
}

