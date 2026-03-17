<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeInvoiceAction
{
    public function handle(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.messages.finalized_locked'),
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor): Invoice {
            $invoice->loadMissing('invoiceItems');

            $invoice->forceFill([
                'status' => InvoiceStatus::FINALIZED,
                'finalized_at' => now(),
                'snapshot_data' => $invoice->invoiceItems
                    ->map(fn ($item): array => [
                        'description' => $item->description,
                        'quantity' => (float) $item->quantity,
                        'unit' => $item->unit,
                        'unit_price' => (float) $item->unit_price,
                        'total' => (float) $item->total,
                    ])
                    ->all(),
                'snapshot_created_at' => now(),
                'generated_at' => now(),
                'generated_by' => (string) $actor->id,
            ])->save();

            return $invoice->fresh(['invoiceItems']);
        });
    }
}
