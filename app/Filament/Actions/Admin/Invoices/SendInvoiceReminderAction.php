<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\User;

class SendInvoiceReminderAction
{
    public function handle(Invoice $invoice, User $actor): InvoiceReminderLog
    {
        $invoice->loadMissing('tenant:id,email');

        return InvoiceReminderLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $actor->id,
            'recipient_email' => (string) ($invoice->tenant?->email ?? ''),
            'channel' => 'email',
            'sent_at' => now(),
            'notes' => __('admin.invoices.messages.reminder_sent'),
        ]);
    }
}
