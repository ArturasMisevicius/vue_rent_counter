<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraCharges;

use App\Enums\ExtraChargeStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtraChargeRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    public function forOrganization(int $organizationId): self
    {
        $request = clone $this;
        $request->organizationId = $organizationId;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'billing_period_id' => [
                'nullable',
                'integer',
                Rule::exists('billing_periods', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists('invoices', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'extra_charge_type_id' => [
                'required',
                'integer',
                Rule::exists('extra_charge_types', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description_for_tenant' => ['nullable', 'string', 'max:5000'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'total_amount' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(ExtraChargeStatus::class)],
            'is_recurring' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'tenant_id',
            'property_id',
            'billing_period_id',
            'invoice_id',
            'extra_charge_type_id',
            'title',
            'description_for_tenant',
            'internal_note',
            'amount',
            'currency',
            'quantity',
            'unit_price',
            'tax_amount',
            'total_amount',
            'status',
            'is_recurring',
            'starts_at',
            'ends_at',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'tenant_id',
            'property_id',
            'billing_period_id',
            'invoice_id',
            'extra_charge_type_id',
            'title',
            'description_for_tenant',
            'internal_note',
            'amount',
            'currency',
            'quantity',
            'unit_price',
            'tax_amount',
            'total_amount',
            'status',
            'starts_at',
            'ends_at',
        ]);

        $this->emptyStringsToNull([
            'billing_period_id',
            'invoice_id',
            'description_for_tenant',
            'internal_note',
            'tax_amount',
            'total_amount',
            'status',
            'starts_at',
            'ends_at',
        ]);

        $this->castBooleans([
            'is_recurring',
        ]);

        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'EUR')),
        ]);
    }
}
