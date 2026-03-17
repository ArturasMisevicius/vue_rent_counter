<?php

namespace App\Actions\Admin\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\User;

class SendInvoiceEmailAction
{
    public function handle(Invoice $invoice, User $actor): InvoiceEmailLog
    {
        $invoice->loadMissing('tenant:id,email');

        return InvoiceEmailLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $actor->id,
            'recipient_email' => (string) ($invoice->tenant?->email ?? ''),
            'subject' => __('admin.invoices.messages.email_subject', ['number' => $invoice->invoice_number]),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
