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
    ];

    protected function casts(): array
    {
        return [
            'project_reference_sequence' => 'integer',
            'project_budget_alert_threshold_percent' => 'integer',
            'project_schedule_alert_threshold_days' => 'integer',
            'notification_preferences' => 'array',
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
}
