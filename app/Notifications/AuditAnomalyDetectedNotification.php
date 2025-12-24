<?php

declare(strict_types=1);

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Audit Anomaly Detected Notification
 * 
 * Notifies administrators when audit anomalies are detected in the system.
 */
final class AuditAnomalyDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $anomalyType,
        private readonly string $severity,
        private readonly string $description,
        private readonly array $details,
        private readonly Carbon $detectedAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $severityColor = match ($this->severity) {
            'high', 'critical' => 'error',
            'medium' => 'warning',
            default => 'info',
        };

        $message = (new MailMessage)
            ->subject(__('audit.notifications.anomaly_detected.subject', [
                'severity' => ucfirst($this->severity),
                'type' => $this->getAnomalyTypeLabel(),
            ]))
            ->greeting(__('audit.notifications.anomaly_detected.greeting'))
            ->line(__('audit.notifications.anomaly_detected.intro', [
                'severity' => $this->severity,
                'type' => $this->getAnomalyTypeLabel(),
                'tenant_id' => $this->tenantId,
            ]))
            ->line($this->description)
            ->line(__('audit.notifications.anomaly_detected.detected_at', [
                'time' => $this->detectedAt->format('Y-m-d H:i:s T'),
            ]));

        // Add details if available
        if (!empty($this->details)) {
            $message->line(__('audit.notifications.anomaly_detected.details_header'));
            
            foreach ($this->getFormattedDetails() as $detail) {
                $message->line("• {$detail}");
            }
        }

        $message->action(
            __('audit.notifications.anomaly_detected.action'),
            route('filament.tenant.pages.dashboard')
        );

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'anomaly_type' => $this->anomalyType,
            'severity' => $this->severity,
            'description' => $this->description,
            'details' => $this->details,
            'detected_at' => $this->detectedAt->toISOString(),
            'title' => __('audit.notifications.anomaly_detected.title'),
            'message' => $this->description,
            'icon' => $this->getSeverityIcon(),
            'color' => $this->getSeverityColor(),
        ];
    }

    /**
     * Get human-readable anomaly type label.
     */
    private function getAnomalyTypeLabel(): string
    {
        return match ($this->anomalyType) {
            'high_change_frequency' => __('audit.anomaly_types.high_change_frequency'),
            'bulk_changes' => __('audit.anomaly_types.bulk_changes'),
            'configuration_rollbacks' => __('audit.anomaly_types.configuration_rollbacks'),
            'unauthorized_access' => __('audit.anomaly_types.unauthorized_access'),
            'data_integrity_issue' => __('audit.anomaly_types.data_integrity_issue'),
            'performance_degradation' => __('audit.anomaly_types.performance_degradation'),
            default => ucfirst(str_replace('_', ' ', $this->anomalyType)),
        };
    }

    /**
     * Get formatted details for display.
     */
    private function getFormattedDetails(): array
    {
        $formatted = [];

        foreach ($this->details as $key => $value) {
            if (is_array($value)) {
                $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . json_encode($value);
            } else {
                $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
            }
        }

        return $formatted;
    }

    /**
     * Get severity icon.
     */
    private function getSeverityIcon(): string
    {
        return match ($this->severity) {
            'high', 'critical' => 'heroicon-o-exclamation-triangle',
            'medium' => 'heroicon-o-exclamation-circle',
            default => 'heroicon-o-information-circle',
        };
    }

    /**
     * Get severity color.
     */
    private function getSeverityColor(): string
    {
        return match ($this->severity) {
            'high', 'critical' => 'danger',
            'medium' => 'warning',
            default => 'info',
        };
    }
}