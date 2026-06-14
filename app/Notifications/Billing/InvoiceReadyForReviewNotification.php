<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class InvoiceReadyForReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.billing_review.notifications.invoice_ready.title'),
            'body' => __('admin.billing_review.notifications.invoice_ready.body', [
                'number' => $this->invoice->invoice_number,
            ]),
            'url' => route('filament.admin.pages.billing-review-center.invoice-review', ['invoice' => $this->invoice->id], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
        ];
    }
}
