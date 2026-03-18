<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminderNotification;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Notification;

class SendInvoiceReminderAction
{
    public function __construct(
        private readonly NotificationPreferenceService $notificationPreferenceService,
    ) {}

    public function handle(Invoice $invoice, User $actor): ?InvoiceReminderLog
    {
        if (! $this->notificationPreferenceService->enabledForUser($actor, NotificationPreferenceService::INVOICE_OVERDUE)) {
            return null;
        }

        $invoice->loadMissing('tenant:id,email');
        $recipientEmail = (string) ($invoice->tenant?->email ?? '');

        if ($recipientEmail === '') {
            return null;
        }

        $sentAt = now();

        Notification::route('mail', $recipientEmail)
            ->notify(new InvoiceOverdueReminderNotification($invoice));

        $log = InvoiceReminderLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $actor->id,
            'recipient_email' => $recipientEmail,
            'channel' => 'email',
            'sent_at' => $sentAt,
            'notes' => __('admin.invoices.messages.reminder_sent'),
        ]);

        $invoice->forceFill([
            'last_reminder_sent_at' => $sentAt,
        ])->save();

        return $log;
    }
}
