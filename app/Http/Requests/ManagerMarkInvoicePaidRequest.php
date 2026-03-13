<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagerMarkInvoicePaidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Controller handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_at' => ['nullable', 'date'],
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
            'payment_reference.string' => __('invoices.validation.payment_reference.string'),
            'payment_reference.max' => __('invoices.validation.payment_reference.max'),
            'paid_amount.numeric' => __('invoices.validation.paid_amount.numeric'),
            'paid_amount.min' => __('invoices.validation.paid_amount.min'),
            'paid_at.date' => __('invoices.validation.paid_at.date'),
        ];
    }
}
