<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Jobs\SendInvoiceReminderJob;
use App\Models\Invoice;
use App\Models\User;
use App\Services\NotificationPreferenceService;

class SendInvoiceReminderAction
{
    public function __construct(
        private readonly NotificationPreferenceService $notificationPreferenceService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Invoice $invoice, User $actor): bool
    {
        $this->subscriptionLimitGuard->ensureCanWrite($invoice->organization_id);

        if (! $this->notificationPreferenceService->enabledForUser($actor, NotificationPreferenceService::INVOICE_OVERDUE)) {
            return false;
        }

        $invoice->loadMissing('tenant:id,email');
        $recipientEmail = (string) ($invoice->tenant?->email ?? '');

        if ($recipientEmail === '') {
            return false;
        }

        SendInvoiceReminderJob::dispatch($invoice->id, $actor->id, $recipientEmail);

        return true;
    }
}
