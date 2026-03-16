<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Perform Rollback Request
 * 
 * Validates requests for performing rollback operations.
 */
final class PerformRollbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole(['admin', 'manager']) ||
            auth()->user()->can('perform_rollbacks')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'audit_log_id' => [
                'required',
                'integer',
                'min:1',
                'exists:audit_logs,id',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
                'min:10',
            ],
            'notify_stakeholders' => [
                'boolean',
            ],
            'confirm_impact' => [
                'required',
                'boolean',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'audit_log_id.required' => __('dashboard.audit.validation.audit_log_id_required'),
            'audit_log_id.integer' => __('dashboard.audit.validation.audit_log_id_integer'),
            'audit_log_id.min' => __('dashboard.audit.validation.audit_log_id_min'),
            'audit_log_id.exists' => __('dashboard.audit.validation.audit_log_id_exists'),
            'reason.string' => __('dashboard.audit.validation.reason_string'),
            'reason.max' => __('dashboard.audit.validation.reason_max'),
            'reason.min' => __('dashboard.audit.validation.reason_min'),
            'notify_stakeholders.boolean' => __('dashboard.audit.validation.notify_stakeholders_boolean'),
            'confirm_impact.required' => __('dashboard.audit.validation.confirm_impact_required'),
            'confirm_impact.boolean' => __('dashboard.audit.validation.confirm_impact_boolean'),
            'confirm_impact.accepted' => __('dashboard.audit.validation.confirm_impact_accepted'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'audit_log_id' => __('dashboard.audit.labels.audit_log_id'),
            'reason' => __('dashboard.audit.labels.rollback_reason'),
            'notify_stakeholders' => __('dashboard.audit.labels.notify_stakeholders'),
            'confirm_impact' => __('dashboard.audit.labels.confirm_impact'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $auditLogId = $this->input('audit_log_id');
            
            if ($auditLogId) {
                // Verify the audit log belongs to the current tenant
                $auditLog = \App\Models\AuditLog::find($auditLogId);
                
                if ($auditLog && !$this->canAccessAuditLog($auditLog)) {
                    $validator->errors()->add(
                        'audit_log_id',
                        __('dashboard.audit.validation.audit_log_access_denied')
                    );
                }
                
                // Verify the audit log is for a supported model type
                if ($auditLog && !$this->isSupportedModelType($auditLog->auditable_type)) {
                    $validator->errors()->add(
                        'audit_log_id',
                        __('dashboard.audit.validation.unsupported_model_type')
                    );
                }
                
                // Verify the audit log is not already a rollback
                if ($auditLog && $auditLog->event === 'rollback') {
                    $validator->errors()->add(
                        'audit_log_id',
                        __('dashboard.audit.validation.cannot_rollback_rollback')
                    );
                }
                
                // Check if rollback is still valid (no subsequent changes)
                if ($auditLog && !$this->isRollbackStillValid($auditLog)) {
                    $validator->errors()->add(
                        'audit_log_id',
                        __('dashboard.audit.validation.rollback_no_longer_valid')
                    );
                }
            }
            
            // Require reason for high-impact rollbacks
            if ($this->isHighImpactRollback($auditLogId) && empty($this->input('reason'))) {
                $validator->errors()->add(
                    'reason',
                    __('dashboard.audit.validation.reason_required_for_high_impact')
                );
            }
        });
    }

    /**
     * Check if the user can access the audit log.
     */
    private function canAccessAuditLog(\App\Models\AuditLog $auditLog): bool
    {
        $user = auth()->user();
        
        // Super admin can access all audit logs
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Regular users can only access their tenant's audit logs
        return $auditLog->tenant_id === $user->currentTeam->id;
    }

    /**
     * Check if the model type supports rollback operations.
     */
    private function isSupportedModelType(string $modelType): bool
    {
        $supportedTypes = [
            \App\Models\UtilityService::class,
            \App\Models\ServiceConfiguration::class,
        ];
        
        return in_array($modelType, $supportedTypes, true);
    }

    /**
     * Check if the rollback is still valid (no subsequent changes).
     */
    private function isRollbackStillValid(\App\Models\AuditLog $auditLog): bool
    {
        // Check if there are any changes after this audit log for the same model
        $subsequentChanges = \App\Models\AuditLog::where('auditable_type', $auditLog->auditable_type)
            ->where('auditable_id', $auditLog->auditable_id)
            ->where('created_at', '>', $auditLog->created_at)
            ->where('event', '!=', 'rollback')
            ->exists();
        
        return !$subsequentChanges;
    }

    /**
     * Check if this is a high-impact rollback that requires a reason.
     */
    private function isHighImpactRollback(?int $auditLogId): bool
    {
        if (!$auditLogId) {
            return false;
        }
        
        $auditLog = \App\Models\AuditLog::find($auditLogId);
        
        if (!$auditLog) {
            return false;
        }
        
        // Consider it high-impact if it affects pricing or calculation formulas
        $highImpactFields = [
            'pricing_model',
            'calculation_formula',
            'rate_schedule',
            'configuration',
        ];
        
        $changedFields = array_keys($auditLog->new_values ?? []);
        
        return !empty(array_intersect($highImpactFields, $changedFields));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'notify_stakeholders' => $this->input('notify_stakeholders', true),
            'confirm_impact' => $this->input('confirm_impact', false),
        ]);
    }
}