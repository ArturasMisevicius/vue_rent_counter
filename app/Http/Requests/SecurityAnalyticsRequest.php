<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SecuritySeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Security Analytics Request Validation
 * 
 * Validates requests for security analytics endpoints
 * with proper filtering and pagination parameters.
 */
final class SecurityAnalyticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Check if user has required permissions
        if (!$user->can('view-security-analytics')) {
            return false;
        }

        // Additional validation for tenant access
        if (!$user->isSuperAdmin() && $this->filled('tenant_id')) {
            $requestedTenantId = $this->input('tenant_id');
            
            // Users can only access their own tenant data
            if ($user->tenant_id !== $requestedTenantId) {
                return false;
            }
        }

        // Rate limiting check
        if (!$this->checkRateLimit()) {
            return false;
        }

        return true;
    }

    /**
     * Check rate limiting for security analytics requests.
     */
    private function checkRateLimit(): bool
    {
        $key = 'security_analytics_' . $this->user()->id;
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 60) { // Max 60 requests per minute
            return false;
        }
        
        cache()->put($key, $attempts + 1, 60);
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Date filtering
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            
            // Violation filtering
            'violation_type' => ['nullable', 'string', Rule::in([
                'csp', 'xss', 'clickjacking', 'mime_sniffing', 'mixed_content'
            ])],
            'severity_level' => ['nullable', Rule::enum(SecuritySeverity::class)],
            'threat_classification' => ['nullable', 'string', Rule::in([
                'false_positive', 'suspicious', 'malicious', 'unknown'
            ])],
            
            // Status filtering
            'unresolved_only' => ['nullable', 'boolean'],
            'resolved_only' => ['nullable', 'boolean'],
            
            // Tenant filtering (for superadmin/admin)
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            
            // Pagination
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            
            // Sorting
            'sort_by' => ['nullable', 'string', Rule::in([
                'created_at', 'severity_level', 'violation_type', 'resolved_at'
            ])],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            
            // Analytics parameters
            'window' => ['nullable', 'string', Rule::in(['1h', '6h', '24h', '7d', '30d'])],
            'sensitivity' => ['nullable', 'string', Rule::in(['low', 'medium', 'high'])],
            
            // Report configuration
            'type' => ['nullable', 'string', Rule::in([
                'summary', 'detailed', 'executive', 'compliance'
            ])],
            'format' => ['nullable', 'string', Rule::in(['json', 'pdf', 'csv'])],
            'include_charts' => ['nullable', 'boolean'],
            'include_recommendations' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.before_or_equal' => __('validation.security.start_date_before_end'),
            'end_date.after_or_equal' => __('validation.security.end_date_after_start'),
            'per_page.max' => __('validation.security.per_page_max'),
            'tenant_id.exists' => __('validation.security.invalid_tenant'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'per_page' => $this->input('per_page', 25),
            'sort_by' => $this->input('sort_by', 'created_at'),
            'sort_direction' => $this->input('sort_direction', 'desc'),
            'window' => $this->input('window', '24h'),
            'sensitivity' => $this->input('sensitivity', 'medium'),
        ]);

        // Apply tenant scoping for non-superadmin users
        if (!$this->user()?->isSuperAdmin()) {
            $this->merge([
                'tenant_id' => tenant()?->id,
            ]);
        }
    }

    /**
     * Get validated data with proper type casting.
     */
    public function validatedWithCasting(): array
    {
        $validated = $this->validated();

        // Cast enum values
        if (isset($validated['severity_level'])) {
            $validated['severity_level'] = SecuritySeverity::from($validated['severity_level']);
        }

        // Cast boolean values
        foreach (['unresolved_only', 'resolved_only', 'include_charts', 'include_recommendations'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = (bool) $validated[$field];
            }
        }

        // Cast date values
        foreach (['start_date', 'end_date'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = $this->date($field);
            }
        }

        return $validated;
    }
}