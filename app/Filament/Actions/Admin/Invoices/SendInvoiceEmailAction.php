<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Invoices\SendInvoiceEmailRequest;
use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\User;

class SendInvoiceEmailAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, User $actor, ?string $recipientEmail = null): bool
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        $invoice->loadMissing('tenant:id,email');

        /** @var SendInvoiceEmailRequest $request */
        $request = new SendInvoiceEmailRequest;
        $validated = $request->validatePayload([
            'recipient_email' => $recipientEmail ?: $invoice->tenant?->email,
        ], $actor);

        $resolvedRecipientEmail = (string) ($validated['recipient_email'] ?? '');

        if ($resolvedRecipientEmail === '') {
            return false;
        }

        SendInvoiceEmailJob::dispatch($invoice->id, $actor->id, $resolvedRecipientEmail);

        return true;
    }
}
