<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceReadingRequestNotification extends Notification
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admin.invoices.reading_request.subject', [
                'number' => $this->invoice->invoice_number,
                'period' => $this->periodName(),
            ]))
            ->greeting(__('admin.invoices.reading_request.greeting'))
            ->line(__('admin.invoices.reading_request.intro', [
                'number' => $this->invoice->invoice_number,
                'period' => $this->periodName(),
            ]))
            ->line(__('admin.invoices.reading_request.period', [
                'from' => $this->periodStart(),
                'to' => $this->periodEnd(),
            ]))
            ->line(__('admin.invoices.reading_request.deadline', [
                'date' => $this->deadlineDisplay(),
            ]))
            ->action(
                __('admin.invoices.reading_request.action'),
                route('tenant.readings.create', ['invoice' => $this->invoice->id]),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('admin.invoices.reading_request.database_title'),
            'body' => __('admin.invoices.reading_request.database_body', [
                'number' => $this->invoice->invoice_number,
                'period' => $this->periodName(),
                'deadline' => $this->deadlineDisplay(),
            ]),
            'url' => route('tenant.readings.create', ['invoice' => $this->invoice->id], false),
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'organization_id' => $this->invoice->organization_id,
            'property_id' => $this->invoice->property_id,
            'billing_period_start' => $this->periodStart(),
            'billing_period_end' => $this->periodEnd(),
            'billing_period_name' => $this->periodName(),
            'reading_submission_deadline' => $this->deadlineDate(),
            'reading_submission_deadline_display' => $this->deadlineDisplay(),
        ];
    }

    private function periodStart(): string
    {
        return $this->invoice->billing_period_start?->toDateString() ?? __('dashboard.not_available');
    }

    private function periodEnd(): string
    {
        return $this->invoice->billing_period_end?->toDateString() ?? __('dashboard.not_available');
    }

    private function periodName(): string
    {
        $metadata = is_array($this->invoice->approval_metadata) ? $this->invoice->approval_metadata : [];
        $periodName = data_get($metadata, 'period.name');

        if (filled($periodName)) {
            return (string) $periodName;
        }

        if ($this->invoice->billingPeriod?->name !== null) {
            return (string) $this->invoice->billingPeriod->name;
        }

        if ($this->invoice->billing_period_start !== null) {
            return $this->invoice->billing_period_start->format('F Y');
        }

        return __('dashboard.not_available');
    }

    private function deadlineDate(): string
    {
        $metadata = is_array($this->invoice->approval_metadata) ? $this->invoice->approval_metadata : [];
        $deadline = data_get($metadata, 'reading_submission_deadline');

        if (filled($deadline)) {
            return (string) $deadline;
        }

        return $this->invoice->due_date?->toDateString() ?? __('dashboard.not_available');
    }

    private function deadlineDisplay(): string
    {
        $deadline = $this->deadlineDate();

        if ($deadline === __('dashboard.not_available')) {
            return $deadline;
        }

        return LocalizedDateFormatter::date($deadline);
    }
}
