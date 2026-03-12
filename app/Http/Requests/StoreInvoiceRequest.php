<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_renter_id' => ['required', 'integer', $this->scopedTenantExistsRule()],
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
            'tenant_renter_id.integer' => __('invoices.validation.tenant_renter_id.integer'),
            'tenant_renter_id.exists' => __('invoices.validation.tenant_renter_id.exists'),
            'billing_period_start.required' => __('invoices.validation.billing_period_start.required'),
            'billing_period_start.date' => __('invoices.validation.billing_period_start.date'),
            'billing_period_end.required' => __('invoices.validation.billing_period_end.required'),
            'billing_period_end.date' => __('invoices.validation.billing_period_end.date'),
            'billing_period_end.after' => __('invoices.validation.billing_period_end.after'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $tenantRenterId = $this->input('tenant_renter_id');

        $this->merge([
            'tenant_renter_id' => $tenantRenterId === null || $tenantRenterId === ''
                ? null
                : (int) $tenantRenterId,
        ]);
    }

    private function scopedTenantExistsRule(): Exists
    {
        return Rule::exists('tenants', 'id')->where(function ($query): void {
            $user = $this->user();

            if ($user === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            if ($user->role === UserRole::SUPERADMIN) {
                return;
            }

            $query->where('tenant_id', $user->tenant_id);
        });
    }
}
