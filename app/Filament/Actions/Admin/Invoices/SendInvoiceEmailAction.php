<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\User;

class SendInvoiceEmailAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, User $actor, ?string $recipientEmail = null): InvoiceEmailLog
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        $invoice->loadMissing('tenant:id,email');

        return InvoiceEmailLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $actor->id,
            'recipient_email' => (string) ($recipientEmail ?: $invoice->tenant?->email ?? ''),
            'subject' => __('admin.invoices.messages.email_subject', ['number' => $invoice->invoice_number]),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
