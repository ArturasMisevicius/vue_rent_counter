<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceOverdueReminderNotification extends Notification
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->invoice->loadMissing([
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
        ]);

        $periodStart = $this->invoice->billing_period_start?->toDateString() ?? __('dashboard.not_available');
        $periodEnd = $this->invoice->billing_period_end?->toDateString() ?? __('dashboard.not_available');
        $downloadUrl = route('tenant.invoices.download', $this->invoice);
        $currencyFormatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        return (new MailMessage)
            ->subject(__('admin.reports.notifications.overdue_subject', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->greeting(__('admin.reports.notifications.overdue_greeting'))
            ->line(__('admin.reports.notifications.overdue_intro', [
                'number' => $this->invoice->invoice_number,
            ]))
            ->line(__('admin.reports.notifications.overdue_period', [
                'from' => $periodStart,
                'to' => $periodEnd,
            ]))
            ->line(__('admin.reports.notifications.overdue_balance', [
                'amount' => $currencyFormatter->formatCurrency((float) $this->invoice->outstanding_balance, $this->invoice->currency),
            ]))
            ->line(__('admin.reports.notifications.overdue_days', [
                'count' => $this->daysOverdue(),
            ]))
            ->action(
                __('admin.reports.notifications.download_invoice'),
                $downloadUrl,
            );
    }

    private function daysOverdue(): int
    {
        $referenceDate = $this->invoice->due_date ?? $this->invoice->billing_period_end;

        if ($referenceDate === null) {
            return 0;
        }

        return (int) max(0, $referenceDate->startOfDay()->diffInDays(now()->startOfDay(), false));
    }
}
