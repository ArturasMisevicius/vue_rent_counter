<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $invoiceId,
        public int $actorId,
        public string $recipientEmail,
        public ?string $personalMessage = null,
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'invoice_number',
            ])
            ->findOrFail($this->invoiceId);

        InvoiceEmailLog::query()->create([
            'invoice_id' => $invoice->id,
            'organization_id' => $invoice->organization_id,
            'sent_by_user_id' => $this->actorId,
            'recipient_email' => $this->recipientEmail,
            'subject' => __('admin.invoices.messages.email_subject', ['number' => $invoice->invoice_number]),
            'status' => 'sent',
            'sent_at' => now(),
            'personal_message' => $this->personalMessage,
        ]);
    }
}
