<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Invoices\SendInvoiceEmailRequest;
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
        /** @var SendInvoiceEmailRequest $request */
        $request = new SendInvoiceEmailRequest;
        $validated = $request->validatePayload([
            'recipient_email' => $recipientEmail ?: $invoice->tenant?->email ?? '',
        ], $actor);

        return InvoiceEmailLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $actor->id,
            'recipient_email' => $validated['recipient_email'],
            'subject' => __('admin.invoices.messages.email_subject', ['number' => $invoice->invoice_number]),
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
