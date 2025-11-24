<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = optional($this->invoice->due_date)->format('Y-m-d');
        $amount = number_format($this->invoice->total_amount, 2);

        return (new MailMessage)
            ->subject("Invoice #{$this->invoice->id} is overdue")
            ->greeting("Hello {$notifiable->name},")
            ->line("Invoice #{$this->invoice->id} is overdue.")
            ->line("Total amount: €{$amount}")
            ->line("Due date: {$dueDate}")
            ->line('Please pay this invoice as soon as possible to avoid service issues.')
            ->action('View Invoice', route('tenant.invoices.show', $this->invoice))
            ->line('If you have already paid, you can ignore this message.');
    }
}
