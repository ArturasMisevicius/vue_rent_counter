<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled at controller level
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'property_id' => ['required', 'exists:properties,id'],
            'lease_start' => ['required', 'date'],
            'lease_end' => ['nullable', 'date', 'after:lease_start'],
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
            'tenant_id.required' => __('tenants.validation.tenant_id.required'),
            'tenant_id.integer' => __('tenants.validation.tenant_id.integer'),
            'name.required' => __('tenants.validation.name.required'),
            'name.string' => __('tenants.validation.name.string'),
            'name.max' => __('tenants.validation.name.max'),
            'email.required' => __('tenants.validation.email.required'),
            'email.email' => __('tenants.validation.email.email'),
            'email.max' => __('tenants.validation.email.max'),
            'phone.string' => __('tenants.validation.phone.string'),
            'phone.max' => __('tenants.validation.phone.max'),
            'property_id.required' => __('tenants.validation.property_id.required'),
            'property_id.exists' => __('tenants.validation.property_id.exists'),
            'lease_start.required' => __('tenants.validation.lease_start.required'),
            'lease_start.date' => __('tenants.validation.lease_start.date'),
            'lease_end.date' => __('tenants.validation.lease_end.date'),
            'lease_end.after' => __('tenants.validation.lease_end.after'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->user()?->tenant_id ?? $this->input('tenant_id'),
        ]);
    }
}
