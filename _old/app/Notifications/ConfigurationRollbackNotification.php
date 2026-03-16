<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Configuration Rollback Notification
 * 
 * Notifies stakeholders when a configuration rollback has been performed,
 * providing details about the rollback and its impact.
 */
final class ConfigurationRollbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AuditLog $rollbackAudit,
        private readonly Model $model,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $modelType = class_basename($this->model);
        $modelName = $this->getModelDisplayName();
        $rollbackReason = $this->rollbackAudit->metadata['rollback_reason'] ?? 'No reason provided';
        $originalAuditId = $this->rollbackAudit->metadata['original_audit_id'] ?? null;
        
        $message = (new MailMessage)
            ->subject(__('dashboard.audit.notifications.configuration_rollback.subject', [
                'model' => $modelType,
                'name' => $modelName,
            ]))
            ->greeting(__('dashboard.audit.notifications.configuration_rollback.greeting'))
            ->line(__('dashboard.audit.notifications.configuration_rollback.intro', [
                'model' => $modelType,
                'name' => $modelName,
                'time' => $this->rollbackAudit->created_at->format('Y-m-d H:i:s'),
            ]))
            ->line(__('dashboard.audit.notifications.configuration_rollback.reason', [
                'reason' => $rollbackReason,
            ]));

        // Add rollback details
        if ($this->rollbackAudit->old_values) {
            $message->line(__('dashboard.audit.notifications.configuration_rollback.details_header'));
            
            foreach ($this->rollbackAudit->old_values as $field => $oldValue) {
                $newValue = $this->rollbackAudit->new_values[$field] ?? 'N/A';
                $message->line("• {$field}: {$oldValue} → {$newValue}");
            }
        }

        // Add impact analysis if available
        $impactAnalysis = $this->rollbackAudit->metadata['impact_analysis'] ?? null;
        if ($impactAnalysis && !empty($impactAnalysis['affected_systems'])) {
            $message->line(__('dashboard.audit.notifications.configuration_rollback.affected_systems'))
                   ->line(implode(', ', $impactAnalysis['affected_systems']));
        }

        // Add warnings if present
        if ($impactAnalysis && !empty($impactAnalysis['warnings'])) {
            $message->line(__('dashboard.audit.notifications.configuration_rollback.warnings_header'));
            foreach ($impactAnalysis['warnings'] as $warning) {
                $message->line("⚠️ {$warning}");
            }
        }

        // Add mitigation steps if available
        if ($impactAnalysis && !empty($impactAnalysis['mitigation_steps'])) {
            $message->line(__('dashboard.audit.notifications.configuration_rollback.mitigation_header'));
            foreach ($impactAnalysis['mitigation_steps'] as $step) {
                $message->line("• {$step}");
            }
        }

        // Add action button to view audit details
        $message->action(
            __('dashboard.audit.notifications.configuration_rollback.action'),
            $this->getAuditDashboardUrl()
        );

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $modelType = class_basename($this->model);
        $modelName = $this->getModelDisplayName();
        $rollbackReason = $this->rollbackAudit->metadata['rollback_reason'] ?? 'No reason provided';
        $impactAnalysis = $this->rollbackAudit->metadata['impact_analysis'] ?? [];

        return [
            'type' => 'configuration_rollback',
            'title' => __('dashboard.audit.notifications.configuration_rollback.title'),
            'message' => __('dashboard.audit.notifications.configuration_rollback.intro', [
                'model' => $modelType,
                'name' => $modelName,
                'time' => $this->rollbackAudit->created_at->format('Y-m-d H:i:s'),
            ]),
            'rollback_audit_id' => $this->rollbackAudit->id,
            'model_type' => get_class($this->model),
            'model_id' => $this->model->id,
            'model_name' => $modelName,
            'rollback_reason' => $rollbackReason,
            'rollback_performed_by' => $this->rollbackAudit->user_id,
            'rollback_performed_at' => $this->rollbackAudit->created_at,
            'original_audit_id' => $this->rollbackAudit->metadata['original_audit_id'] ?? null,
            'affected_systems' => $impactAnalysis['affected_systems'] ?? [],
            'warnings' => $impactAnalysis['warnings'] ?? [],
            'mitigation_steps' => $impactAnalysis['mitigation_steps'] ?? [],
            'has_critical_impact' => $impactAnalysis['has_critical_impact'] ?? false,
            'changed_fields' => array_keys($this->rollbackAudit->new_values ?? []),
            'audit_dashboard_url' => $this->getAuditDashboardUrl(),
        ];
    }

    /**
     * Get display name for the model.
     */
    private function getModelDisplayName(): string
    {
        // Try to get a meaningful name from the model
        if (method_exists($this->model, 'getDisplayName')) {
            return $this->model->getDisplayName();
        }

        if (isset($this->model->name)) {
            return $this->model->name;
        }

        if (isset($this->model->title)) {
            return $this->model->title;
        }

        // Fallback to model type and ID
        return class_basename($this->model) . ' #' . $this->model->id;
    }

    /**
     * Get URL to audit dashboard.
     */
    private function getAuditDashboardUrl(): string
    {
        // Return URL to audit dashboard or specific audit log view
        // This would typically be a Filament resource URL
        return url('/admin/audit-logs/' . $this->rollbackAudit->id);
    }
}