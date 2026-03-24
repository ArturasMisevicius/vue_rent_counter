<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Notifications\InvoiceOverdueReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendInvoiceReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $invoiceId,
        public int $actorId,
        public string $recipientEmail,
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'billing_period_start',
                'billing_period_end',
                'due_date',
                'last_reminder_sent_at',
            ])
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ])
            ->findOrFail($this->invoiceId);

        $sentAt = now();

        Notification::route('mail', $this->recipientEmail)
            ->notify(new InvoiceOverdueReminderNotification($invoice));

        InvoiceReminderLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $this->actorId,
            'recipient_email' => $this->recipientEmail,
            'channel' => 'email',
            'sent_at' => $sentAt,
            'notes' => __('admin.invoices.messages.reminder_sent'),
        ]);

        $invoice->forceFill([
            'last_reminder_sent_at' => $sentAt,
        ])->save();
    }
}
