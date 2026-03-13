<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceItemRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
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
            'description.required' => __('invoices.validation.items.description.required'),
            'description.string' => __('invoices.validation.items.description.string'),
            'description.max' => __('invoices.validation.items.description.max'),
            'quantity.required' => __('invoices.validation.items.quantity.required'),
            'quantity.numeric' => __('invoices.validation.items.quantity.numeric'),
            'quantity.min' => __('invoices.validation.items.quantity.min'),
            'unit_price.required' => __('invoices.validation.items.unit_price.required'),
            'unit_price.numeric' => __('invoices.validation.items.unit_price.numeric'),
            'unit_price.min' => __('invoices.validation.items.unit_price.min'),
            'total_price.required' => __('invoices.validation.items.total_price.required'),
            'total_price.numeric' => __('invoices.validation.items.total_price.numeric'),
            'total_price.min' => __('invoices.validation.items.total_price.min'),
        ];
    }
}
