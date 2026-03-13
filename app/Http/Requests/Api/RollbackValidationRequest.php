<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Rollback Validation Request
 * 
 * Validates requests for rollback validation operations.
 */
final class RollbackValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole(['admin', 'manager']) ||
            auth()->user()->can('validate_rollbacks')
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
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'audit_log_id' => __('dashboard.audit.labels.audit_log_id'),
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
}