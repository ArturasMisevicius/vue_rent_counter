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
            'tenant_ids.*' => ['integer', 'exists:tenants,id'],
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
            'billing_period_start.required' => __('invoices.validation.billing_period_start.required'),
            'billing_period_start.date' => __('invoices.validation.billing_period_start.date'),
            'billing_period_end.required' => __('invoices.validation.billing_period_end.required'),
            'billing_period_end.date' => __('invoices.validation.billing_period_end.date'),
            'billing_period_end.after' => __('invoices.validation.billing_period_end.after'),
            'tenant_ids.array' => __('invoices.validation.tenant_ids.array'),
            'tenant_ids.*.integer' => __('invoices.validation.tenant_ids.integer'),
            'tenant_ids.*.exists' => __('invoices.validation.tenant_ids.exists'),
        ];
    }
}
