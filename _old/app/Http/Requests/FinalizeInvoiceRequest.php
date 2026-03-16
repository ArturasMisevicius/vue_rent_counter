<?php

namespace App\Http\Requests;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FinalizeInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies/gates
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No additional input fields required for finalization
            // Validation is done in withValidator
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateInvoiceCanBeFinalized($validator);
        });
    }

    /**
     * Validate that the invoice can be finalized.
     * Implements Property 11: Invoice immutability after finalization
     * Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5
     */
    protected function validateInvoiceCanBeFinalized(Validator $validator): void
    {
        $invoice = $this->route('invoice'); // Assumes route model binding
        
        if (!$invoice instanceof Invoice) {
            $validator->errors()->add('invoice', __('invoices.validation.finalize.not_found'));
            return;
        }

        // Check if invoice is already finalized
        if ($invoice->status === InvoiceStatus::FINALIZED || $invoice->finalized_at !== null) {
            $validator->errors()->add(
                'invoice',
                __('invoices.validation.finalize.already_finalized')
            );
            return;
        }

        // Check if invoice has items
        if ($invoice->items()->count() === 0) {
            $validator->errors()->add(
                'invoice',
                __('invoices.validation.finalize.no_items')
            );
            return;
        }

        // Check if invoice has a valid total amount
        if ($invoice->total_amount <= 0) {
            $validator->errors()->add(
                'invoice',
                __('invoices.validation.finalize.invalid_total')
            );
            return;
        }

        // Check if all invoice items have valid data
        foreach ($invoice->items as $item) {
            if (empty($item->description) || $item->unit_price < 0 || $item->quantity < 0) {
                $validator->errors()->add(
                    'invoice',
                    __('invoices.validation.finalize.invalid_items')
                );
                return;
            }
        }

        // Check if billing period is valid
        if ($invoice->billing_period_start >= $invoice->billing_period_end) {
            $validator->errors()->add(
                'invoice',
                __('invoices.validation.finalize.invalid_period')
            );
            return;
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice' => __('invoices.validation.invoice'),
        ];
    }
}
