<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * UpdateTariffRequest
 * 
 * Validates tariff update operations.
 * Extends StoreTariffRequest to reuse validation logic.
 * 
 * Security:
 * - Validates all input fields
 * - Prevents invalid tariff configurations
 * - Validates time-of-use zones
 * - Prevents overlapping time ranges
 * 
 * Requirements:
 * - 2.1: Store tariff configuration as JSON
 * - 2.2: Validate time-of-use zones
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.2: Admin can update tariffs
 * 
 * @package App\Http\Requests
 */
final class UpdateTariffRequest extends StoreTariffRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by TariffPolicy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Make all fields optional for updates (partial updates allowed)
        foreach ($rules as $key => $rule) {
            if (is_array($rule) && in_array('required', $rule, true)) {
                $rules[$key] = array_diff($rule, ['required']);
                $rules[$key][] = 'sometimes';
            }
        }

        // Keep provider_id required if provided
        if (isset($rules['provider_id'])) {
            $rules['provider_id'] = ['sometimes', 'required', 'exists:providers,id'];
        }

        // Keep name required if provided
        if (isset($rules['name'])) {
            $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
        }

        // Keep configuration required if provided
        if (isset($rules['configuration'])) {
            $rules['configuration'] = ['sometimes', 'required', 'array'];
        }

        // Keep active_from required if provided
        if (isset($rules['active_from'])) {
            $rules['active_from'] = ['sometimes', 'required', 'date'];
        }

        return $rules;
    }
}
