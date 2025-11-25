<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Building;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Calculate Gyvatukas Request
 *
 * Validates input for gyvatukas (circulation fee) calculations.
 * Ensures data integrity and prevents malicious input.
 *
 * ## Validation Rules
 * - Building must exist and be active
 * - Billing month must be valid date
 * - Distribution method must be 'equal' or 'area'
 * - Building must have properties
 * - User must be authorized
 *
 * ## Security Requirements
 * - Requirement 1.2: Input validation
 * - Requirement 7.3: Cross-tenant access prevention
 * - Requirement 11.1: Role-based access control
 *
 * @package App\Http\Requests
 */
final class CalculateGyvatukasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin and manager roles can calculate
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Superadmin always authorized
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Tenants cannot calculate
        if ($user->role === UserRole::TENANT) {
            return false;
        }

        // Admin and Manager must be authorized via policy
        $building = $this->route('building');
        if ($building instanceof Building) {
            return $user->can('calculate', [Building::class, $building]);
        }

        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'building_id' => [
                'required',
                'integer',
                'exists:buildings,id',
                function ($attribute, $value, $fail) {
                    $building = Building::find($value);
                    
                    if (!$building) {
                        $fail(__('validation.exists', ['attribute' => 'building']));
                        return;
                    }

                    // Verify building has properties
                    if ($building->properties()->count() === 0) {
                        $fail(__('gyvatukas.validation.no_properties'));
                        return;
                    }

                    // Verify building belongs to user's tenant (unless superadmin)
                    $user = $this->user();
                    if ($user && $user->role !== UserRole::SUPERADMIN) {
                        if ($building->tenant_id !== $user->tenant_id) {
                            $fail(__('gyvatukas.validation.unauthorized_building'));
                            return;
                        }
                    }
                },
            ],
            'billing_month' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:today',
                'after:2020-01-01', // Reasonable historical limit
            ],
            'distribution_method' => [
                'sometimes',
                'string',
                Rule::in(['equal', 'area']),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'building_id' => __('gyvatukas.attributes.building'),
            'billing_month' => __('gyvatukas.attributes.billing_month'),
            'distribution_method' => __('gyvatukas.attributes.distribution_method'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'building_id.required' => __('gyvatukas.validation.building_required'),
            'building_id.exists' => __('gyvatukas.validation.building_not_found'),
            'billing_month.required' => __('gyvatukas.validation.billing_month_required'),
            'billing_month.date' => __('gyvatukas.validation.billing_month_invalid'),
            'billing_month.before_or_equal' => __('gyvatukas.validation.billing_month_future'),
            'billing_month.after' => __('gyvatukas.validation.billing_month_too_old'),
            'distribution_method.in' => __('gyvatukas.validation.distribution_method_invalid'),
        ];
    }

    /**
     * Get the validated building instance.
     *
     * @return Building
     */
    public function getBuilding(): Building
    {
        return Building::findOrFail($this->validated('building_id'));
    }

    /**
     * Get the validated billing month as Carbon instance.
     *
     * @return \Carbon\Carbon
     */
    public function getBillingMonth(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->validated('billing_month'));
    }

    /**
     * Get the validated distribution method.
     *
     * @return string
     */
    public function getDistributionMethod(): string
    {
        return $this->validated('distribution_method', 'equal');
    }
}
