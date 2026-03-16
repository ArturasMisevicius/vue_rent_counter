<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * GenerateInvoiceRequest
 * 
 * Validates invoice generation parameters.
 * 
 * Requirements:
 * - Input validation for tenant and date range
 * - Duplicate invoice prevention
 * - Date range validation
 * 
 * @package App\Http\Requests
 */
class GenerateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $tenant = Tenant::find($this->input('tenant_id'));
        
        if (!$tenant) {
            return false;
        }

        return $this->user()->can('generateInvoice', [Tenant::class, $tenant]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],
            'period_start' => [
                'required',
                'date',
                'before_or_equal:period_end',
                'before_or_equal:today',
            ],
            'period_end' => [
                'required',
                'date',
                'after_or_equal:period_start',
                'before_or_equal:today',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Validate tenant is active
            $tenant = Tenant::find($this->input('tenant_id'));
            if ($tenant && $tenant->trashed()) {
                $validator->errors()->add('tenant_id', __('billing.validation.tenant_inactive'));
            }

            // Validate date range is reasonable (not more than 3 months)
            $start = Carbon::parse($this->input('period_start'));
            $end = Carbon::parse($this->input('period_end'));
            
            if ($start->diffInMonths($end) > 3) {
                $validator->errors()->add('period_end', __('billing.validation.period_too_long'));
            }

            // Check for duplicate invoices
            if ($tenant) {
                $existingInvoice = Invoice::where('tenant_renter_id', $tenant->id)
                    ->where('billing_period_start', $start->toDateString())
                    ->where('billing_period_end', $end->toDateString())
                    ->whereIn('status', ['draft', 'finalized', 'paid'])
                    ->exists();

                if ($existingInvoice) {
                    $validator->errors()->add('period_start', __('billing.validation.duplicate_invoice'));
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tenant_id' => __('billing.fields.tenant'),
            'period_start' => __('billing.fields.period_start'),
            'period_end' => __('billing.fields.period_end'),
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
            'tenant_id.required' => __('billing.validation.tenant_required'),
            'tenant_id.exists' => __('billing.validation.tenant_not_found'),
            'period_start.required' => __('billing.validation.period_start_required'),
            'period_start.before_or_equal' => __('billing.validation.period_start_future'),
            'period_end.required' => __('billing.validation.period_end_required'),
            'period_end.before_or_equal' => __('billing.validation.period_end_future'),
        ];
    }
}
