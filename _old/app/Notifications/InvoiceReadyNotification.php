<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invoice Ready Notification
 *
 * Sends an email to the tenant when their invoice is ready.
 * Includes the invoice PDF as an attachment.
 *
 * ## Usage
 * ```php
 * $invoice = Invoice::find(1);
 * $tenant->notify(new InvoiceReadyNotification($invoice));
 * ```
 *
 * ## Email Contents
 * - Subject: "Invoice #{invoice_number} is ready"
 * - Greeting to tenant
 * - Invoice details (number, billing period, total amount)
 * - PDF attachment
 *
 * @see InvoicePdfService For PDF generation
 */
class InvoiceReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invoiceNumber = $this->invoice->invoice_number ?? 'INV-' . $this->invoice->id;

        // Generate PDF for attachment
        $pdfService = app(InvoicePdfService::class);
        $pdf = $pdfService->generate($this->invoice);
        $pdfContent = $pdf->output();

        // Generate filename (same logic as InvoicePdfService::generateFilename)
        $sanitizedNumber = preg_replace('/[^a-zA-Z0-9-_]/', '_', $invoiceNumber);
        $filename = sprintf('invoice_%s.pdf', $sanitizedNumber);

        return (new MailMessage)
            ->subject("Invoice {$invoiceNumber} is ready")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your invoice {$invoiceNumber} is ready for review.")
            ->line("**Billing Period:** {$this->invoice->billing_period_start->format('Y-m-d')} to {$this->invoice->billing_period_end->format('Y-m-d')}")
            ->line("**Total Amount:** €" . number_format((float) $this->invoice->total_amount, 2))
            ->line('Please find your invoice attached to this email.')
            ->attachData($pdfContent, $filename, [
                'mime' => 'application/pdf',
            ])
            ->line('If you have any questions, please contact your property manager.')
            ->salutation('Thank you for your business!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total_amount' => $this->invoice->total_amount,
        ];
    }
}
