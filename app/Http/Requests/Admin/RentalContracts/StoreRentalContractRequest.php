<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\RentalContracts;

use App\Enums\RentalContractStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\RentalContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalContractRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    private ?RentalContract $ignoredContract = null;

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

    public function ignoreContract(?RentalContract $contract): self
    {
        $request = clone $this;
        $request->ignoredContract = $contract;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'property_id' => ['required', 'integer'],
            'property_assignment_id' => ['nullable', 'integer'],
            'contract_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rental_contracts', 'contract_number')
                    ->where(fn ($query) => $query->where('organization_id', $this->organizationId))
                    ->ignore($this->ignoredContract?->getKey()),
            ],
            'status' => ['required', Rule::enum(RentalContractStatus::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'signed_date' => ['nullable', 'date'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'tenant_visible' => ['required', 'boolean'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'tenant_visible_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'tenant_id.required' => ['required', 'tenant_id'],
            'tenant_id.integer' => ['integer', 'tenant_id'],
            'property_id.required' => ['required', 'property_id'],
            'property_id.integer' => ['integer', 'property_id'],
            'property_assignment_id.integer' => ['integer', 'property_assignment_id'],
            'contract_number.required' => ['required', 'contract_number'],
            'contract_number.max' => ['max.string', 'contract_number', ['max' => 255]],
            'contract_number.unique' => ['unique', 'contract_number'],
            'status.required' => ['required', 'status'],
            'start_date.required' => ['required', 'start_date'],
            'start_date.date' => ['date', 'start_date'],
            'end_date.required' => ['required', 'end_date'],
            'end_date.date' => ['date', 'end_date'],
            'end_date.after' => ['after', 'end_date', ['date' => $this->translateAttribute('start_date')]],
            'signed_date.date' => ['date', 'signed_date'],
            'rent_amount.numeric' => ['numeric', 'rent_amount'],
            'rent_amount.min' => ['min.numeric', 'rent_amount', ['min' => 0]],
            'deposit_amount.numeric' => ['numeric', 'deposit_amount'],
            'deposit_amount.min' => ['min.numeric', 'deposit_amount', ['min' => 0]],
            'currency.required' => ['required', 'currency'],
            'currency.size' => ['size.string', 'currency', ['size' => 3]],
            'tenant_visible.boolean' => ['boolean', 'tenant_visible'],
            'internal_notes.max' => ['max.string', 'internal_notes', ['max' => 10000]],
            'tenant_visible_notes.max' => ['max.string', 'tenant_visible_notes', ['max' => 10000]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'tenant_id',
            'property_id',
            'property_assignment_id',
            'contract_number',
            'status',
            'start_date',
            'end_date',
            'signed_date',
            'rent_amount',
            'deposit_amount',
            'currency',
            'tenant_visible',
            'internal_notes',
            'tenant_visible_notes',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'tenant_id',
            'property_id',
            'property_assignment_id',
            'contract_number',
            'status',
            'start_date',
            'end_date',
            'signed_date',
            'rent_amount',
            'deposit_amount',
            'currency',
            'internal_notes',
            'tenant_visible_notes',
        ]);

        $this->emptyStringsToNull([
            'property_assignment_id',
            'signed_date',
            'rent_amount',
            'deposit_amount',
            'internal_notes',
            'tenant_visible_notes',
        ]);

        $this->castBooleans(['tenant_visible']);

        $this->merge([
            'status' => $this->input('status', RentalContractStatus::DRAFT->value),
            'tenant_visible' => $this->boolean('tenant_visible'),
            'currency' => strtoupper((string) $this->input('currency', 'EUR')),
        ]);
    }
}
