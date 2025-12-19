<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

/**
 * Security Alert Notification
 * 
 * Sends security alerts via multiple channels (email, Slack, etc.)
 * for critical security incidents and violations.
 */
final class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $alertType,
        private readonly array $alertData
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // Add Slack for critical alerts
        if (in_array($this->alertType, ['critical_violation', 'security_anomaly'])) {
            $channels[] = 'slack';
        }

        // Add database for audit trail
        $channels[] = 'database';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Security Alert')
            ->line($this->getMessage())
            ->line($this->getDetails());

        if ($this->isUrgent()) {
            $message->priority(1); // High priority
        }

        return $message->action('View Security Dashboard', url('/admin/security/dashboard'));
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $color = $this->getSlackColor();
        
        return (new SlackMessage)
            ->to('#security-alerts')
            ->content($this->getMessage())
            ->attachment(function ($attachment) use ($color) {
                $attachment->title($this->getSubject())
                    ->fields($this->getSlackFields())
                    ->color($color)
                    ->timestamp(now());
            });
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_type' => $this->alertType,
            'subject' => $this->getSubject(),
            'message' => $this->getMessage(),
            'data' => $this->sanitizeAlertData(),
            'severity' => $this->getSeverity(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get alert subject based on type.
     */
    private function getSubject(): string
    {
        return match ($this->alertType) {
            'critical_violation' => '🚨 Critical Security Violation Detected',
            'high_violation_rate' => '⚠️ High Security Violation Rate',
            'security_anomaly' => '🔍 Security Anomaly Detected',
            'compliance_threshold' => '📊 Compliance Threshold Exceeded',
            default => '🔒 Security Alert',
        };
    }

    /**
     * Get alert message.
     */
    private function getMessage(): string
    {
        return $this->alertData['message'] ?? 'A security event has been detected that requires attention.';
    }

    /**
     * Get detailed information.
     */
    private function getDetails(): string
    {
        return match ($this->alertType) {
            'critical_violation' => $this->getCriticalViolationDetails(),
            'high_violation_rate' => $this->getHighRateDetails(),
            'security_anomaly' => $this->getAnomalyDetails(),
            'compliance_threshold' => $this->getComplianceDetails(),
            default => 'Please check the security dashboard for more information.',
        };
    }

    /**
     * Get critical violation details.
     */
    private function getCriticalViolationDetails(): string
    {
        $violation = $this->alertData['violation'] ?? null;
        
        if (!$violation) {
            return 'Critical violation details unavailable.';
        }

        return sprintf(
            'Violation ID: %s | Type: %s | Severity: %s | Classification: %s | Time: %s',
            $violation->id,
            $violation->violation_type,
            $violation->severity_level->value,
            $violation->threat_classification->value,
            $violation->created_at->format('Y-m-d H:i:s T')
        );
    }

    /**
     * Get high rate details.
     */
    private function getHighRateDetails(): string
    {
        $count = $this->alertData['count'] ?? 0;
        $tenantId = $this->alertData['tenant_id'] ?? 'unknown';

        return sprintf(
            'Tenant: %s | Violations: %d in 10 minutes | Threshold exceeded',
            $tenantId,
            $count
        );
    }

    /**
     * Get anomaly details.
     */
    private function getAnomalyDetails(): string
    {
        $anomaly = $this->alertData['anomaly'] ?? [];
        
        return sprintf(
            'Type: %s | Risk Score: %.2f | Description: %s',
            $anomaly['type'] ?? 'unknown',
            $anomaly['risk_score'] ?? 0,
            $anomaly['description'] ?? 'No description available'
        );
    }

    /**
     * Get compliance details.
     */
    private function getComplianceDetails(): string
    {
        return sprintf(
            'Violation Type: %s | Count: %d | Threshold: %d | Time Window: 24 hours',
            $this->alertData['type'] ?? 'unknown',
            $this->alertData['count'] ?? 0,
            $this->alertData['threshold'] ?? 0
        );
    }

    /**
     * Check if alert is urgent.
     */
    private function isUrgent(): bool
    {
        return in_array($this->alertType, ['critical_violation', 'security_anomaly']);
    }

    /**
     * Get Slack color based on alert type.
     */
    private function getSlackColor(): string
    {
        return match ($this->alertType) {
            'critical_violation' => 'danger',
            'security_anomaly' => 'warning',
            'high_violation_rate' => 'warning',
            'compliance_threshold' => '#ff9500',
            default => 'good',
        };
    }

    /**
     * Get Slack fields.
     */
    private function getSlackFields(): array
    {
        $fields = [
            'Alert Type' => $this->alertType,
            'Timestamp' => now()->format('Y-m-d H:i:s T'),
        ];

        // Add specific fields based on alert type
        switch ($this->alertType) {
            case 'critical_violation':
                $violation = $this->alertData['violation'] ?? null;
                if ($violation) {
                    $fields['Violation ID'] = $violation->id;
                    $fields['Severity'] = $violation->severity_level->value;
                    $fields['Type'] = $violation->violation_type;
                }
                break;

            case 'high_violation_rate':
                $fields['Tenant ID'] = $this->alertData['tenant_id'] ?? 'unknown';
                $fields['Violation Count'] = $this->alertData['count'] ?? 0;
                break;

            case 'security_anomaly':
                $anomaly = $this->alertData['anomaly'] ?? [];
                $fields['Risk Score'] = $anomaly['risk_score'] ?? 0;
                $fields['Anomaly Type'] = $anomaly['type'] ?? 'unknown';
                break;
        }

        return $fields;
    }

    /**
     * Get alert severity level.
     */
    private function getSeverity(): string
    {
        return match ($this->alertType) {
            'critical_violation' => 'critical',
            'security_anomaly' => 'high',
            'high_violation_rate' => 'medium',
            'compliance_threshold' => 'medium',
            default => 'low',
        };
    }

    /**
     * Sanitize alert data for storage.
     */
    private function sanitizeAlertData(): array
    {
        $sanitized = $this->alertData;

        // Remove sensitive information
        unset($sanitized['violation']); // Don't store full violation object
        
        // Sanitize any URIs
        if (isset($sanitized['blocked_uri'])) {
            $sanitized['blocked_uri'] = $this->sanitizeUri($sanitized['blocked_uri']);
        }

        return $sanitized;
    }

    /**
     * Sanitize URI for storage.
     */
    private function sanitizeUri(string $uri): string
    {
        // Remove tokens and sensitive parameters
        $uri = preg_replace('/[?&]token=[^&]*/', '?token=***', $uri);
        $uri = preg_replace('/[?&]key=[^&]*/', '?key=***', $uri);
        
        return substr($uri, 0, 255);
    }
}