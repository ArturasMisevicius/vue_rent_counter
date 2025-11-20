<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBulkInvoicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after:billing_period_start'],
            'tenant_ids' => ['nullable', 'array'],
            'tenant_ids.*' => ['exists:tenants,id'],
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
            'billing_period_start.required' => 'Billing period start date is required',
            'billing_period_start.date' => 'Billing period start must be a valid date',
            'billing_period_end.required' => 'Billing period end date is required',
            'billing_period_end.date' => 'Billing period end must be a valid date',
            'billing_period_end.after' => 'Billing period end must be after start date',
            'tenant_ids.array' => 'Tenant IDs must be an array',
            'tenant_ids.*.exists' => 'One or more selected tenants do not exist',
        ];
    }
}
