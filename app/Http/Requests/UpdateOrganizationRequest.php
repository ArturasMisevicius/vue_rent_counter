<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($organization?->id)],
            'organization_name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
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
            'name.required' => __('organizations.validation.name.required'),
            'name.string' => __('organizations.validation.name.string'),
            'name.max' => __('organizations.validation.name.max'),
            'email.required' => __('organizations.validation.email.required'),
            'email.string' => __('organizations.validation.email.string'),
            'email.email' => __('organizations.validation.email.email'),
            'email.max' => __('organizations.validation.email.max'),
            'email.unique' => __('organizations.validation.email.unique'),
            'organization_name.required' => __('organizations.validation.organization_name.required'),
            'organization_name.string' => __('organizations.validation.organization_name.string'),
            'organization_name.max' => __('organizations.validation.organization_name.max'),
            'is_active.boolean' => __('organizations.validation.is_active.boolean'),
        ];
    }
}
