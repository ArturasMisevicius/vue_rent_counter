<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraCharges;

use App\Enums\ExtraChargeTypeCode;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtraChargeTypeRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    private ?int $ignoreId = null;

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

    public function ignore(?int $id): self
    {
        $request = clone $this;
        $request->ignoreId = $id;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('extra_charge_types', 'name')
                    ->where(fn ($query) => $query->where('organization_id', $this->organizationId))
                    ->ignore($this->ignoreId),
            ],
            'type' => ['required', Rule::enum(ExtraChargeTypeCode::class)],
            'default_amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'is_recurring' => ['required', 'boolean'],
            'is_taxable' => ['required', 'boolean'],
            'tenant_visible_by_default' => ['required', 'boolean'],
            'requires_comment' => ['required', 'boolean'],
            'requires_attachment' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'name',
            'type',
            'default_amount',
            'currency',
            'is_recurring',
            'is_taxable',
            'tenant_visible_by_default',
            'requires_comment',
            'requires_attachment',
            'is_active',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'type',
            'default_amount',
            'currency',
        ]);

        $this->castBooleans([
            'is_recurring',
            'is_taxable',
            'tenant_visible_by_default',
            'requires_comment',
            'requires_attachment',
            'is_active',
        ]);

        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'EUR')),
        ]);
    }
}
