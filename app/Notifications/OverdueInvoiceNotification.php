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
            ->subject(__('notifications.overdue_invoice.subject', ['id' => $this->invoice->id]))
            ->greeting(__('notifications.overdue_invoice.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.overdue_invoice.overdue', ['id' => $this->invoice->id]))
            ->line(__('notifications.overdue_invoice.amount', ['amount' => "€{$amount}"]))
            ->line(__('notifications.overdue_invoice.due_date', ['date' => $dueDate]))
            ->line(__('notifications.overdue_invoice.pay_notice'))
            ->action(__('notifications.overdue_invoice.action'), route('tenant.invoices.show', $this->invoice))
            ->line(__('notifications.overdue_invoice.ignore'));
    }
}
