<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    /** @use HasFactory<OrganizationSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'billing_contact_name',
        'billing_contact_email',
        'billing_contact_phone',
        'payment_instructions',
        'invoice_footer',
        'project_reference_prefix',
        'project_reference_sequence',
        'project_completion_mode',
        'project_budget_alert_threshold_percent',
        'project_schedule_alert_threshold_days',
        'notification_preferences',
        'auto_generation_enabled',
        'billing_frequency',
        'invoice_generation_day',
        'reading_deadline_day',
        'payment_due_days',
        'send_created_notification',
        'send_reminders',
        'reminder_days_before_deadline',
        'timezone',
        'default_currency',
        'kyc_required',
        'required_document_types',
        'require_expiry_date',
        'block_portal_until_verified',
        'block_invoice_download_until_verified',
        'block_reading_submission_until_verified',
    ];

    protected function casts(): array
    {
        return [
            'project_reference_sequence' => 'integer',
            'project_budget_alert_threshold_percent' => 'integer',
            'project_schedule_alert_threshold_days' => 'integer',
            'notification_preferences' => 'array',
            'auto_generation_enabled' => 'boolean',
            'invoice_generation_day' => 'integer',
            'reading_deadline_day' => 'integer',
            'payment_due_days' => 'integer',
            'send_created_notification' => 'boolean',
            'send_reminders' => 'boolean',
            'reminder_days_before_deadline' => 'array',
            'kyc_required' => 'boolean',
            'required_document_types' => 'array',
            'require_expiry_date' => 'boolean',
            'block_portal_until_verified' => 'boolean',
            'block_invoice_download_until_verified' => 'boolean',
            'block_reading_submission_until_verified' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function usesAutomaticProjectCompletion(): bool
    {
        return $this->project_completion_mode === 'automatic_from_tasks';
    }

    /**
     * @return array<string, mixed>
     */
    public function billingSchedule(): array
    {
        return [
            'auto_generation_enabled' => (bool) $this->auto_generation_enabled,
            'billing_frequency' => (string) ($this->billing_frequency ?: 'monthly'),
            'invoice_generation_day' => max(1, min(28, (int) ($this->invoice_generation_day ?: 1))),
            'reading_deadline_day' => max(1, min(28, (int) ($this->reading_deadline_day ?: 5))),
            'payment_due_days' => max(0, (int) ($this->payment_due_days ?? 14)),
            'send_created_notification' => (bool) ($this->send_created_notification ?? true),
            'send_reminders' => (bool) ($this->send_reminders ?? true),
            'reminder_days_before_deadline' => $this->reminder_days_before_deadline ?? [3, 1],
            'timezone' => (string) ($this->timezone ?: 'UTC'),
            'default_currency' => strtoupper((string) ($this->default_currency ?: 'EUR')),
        ];
    }
}
