<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after:billing_period_start'],
            'tenant_ids' => ['nullable', 'array'],
            'tenant_ids.*' => ['integer', $this->scopedTenantExistsRule()],
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

    protected function prepareForValidation(): void
    {
        /** @var Collection<int, mixed> $normalizedTenantIds */
        $normalizedTenantIds = collect($this->input('tenant_ids', []))
            ->filter(fn ($tenantId) => $tenantId !== null && $tenantId !== '')
            ->map(fn ($tenantId) => (int) $tenantId)
            ->unique()
            ->values();

        $this->merge([
            'tenant_ids' => $normalizedTenantIds->isEmpty() ? null : $normalizedTenantIds->all(),
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
