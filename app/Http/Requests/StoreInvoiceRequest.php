<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'tenant_renter_id' => ['required', 'exists:tenants,id'],
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after:billing_period_start'],
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
            'tenant_renter_id.required' => __('invoices.validation.tenant_renter_id.required'),
            'tenant_renter_id.exists' => __('invoices.validation.tenant_renter_id.exists'),
            'billing_period_start.required' => __('invoices.validation.billing_period_start.required'),
            'billing_period_start.date' => __('invoices.validation.billing_period_start.date'),
            'billing_period_end.required' => __('invoices.validation.billing_period_end.required'),
            'billing_period_end.date' => __('invoices.validation.billing_period_end.date'),
            'billing_period_end.after' => __('invoices.validation.billing_period_end.after'),
        ];
    }
}
