<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\BillingGenerationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class BillingGenerationSummaryNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly BillingGenerationLog $log,
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
            'title' => __('admin.billing_generation.notifications.summary.title'),
            'body' => __('admin.billing_generation.notifications.summary.body', [
                'created' => $this->log->created_count,
                'skipped' => $this->log->skipped_count,
                'errors' => $this->log->error_count,
            ]),
            'url' => route('filament.admin.resources.billing-generation-logs.view', $this->log, false),
            'billing_generation_log_id' => $this->log->id,
            'billing_period_id' => $this->log->billing_period_id,
            'organization_id' => $this->log->organization_id,
            'status' => $this->log->status,
            'created_count' => $this->log->created_count,
            'skipped_count' => $this->log->skipped_count,
            'warning_count' => $this->log->warning_count,
            'error_count' => $this->log->error_count,
            'notified_tenants_count' => $this->log->notified_tenants_count,
        ];
    }
}
