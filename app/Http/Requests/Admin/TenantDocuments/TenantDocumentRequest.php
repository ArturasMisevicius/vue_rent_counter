<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\TenantDocuments;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantDocumentRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        return ($this->user()?->isAdminLike() ?? false) || ($this->user()?->isTenant() ?? false);
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
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'related_type' => ['nullable', 'string', 'max:255'],
            'related_id' => ['nullable', 'integer'],
            'document_type' => ['required', Rule::enum(TenantDocumentType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description_for_tenant' => [
                Rule::requiredIf(fn (): bool => $this->boolean('tenant_visible')),
                'nullable',
                'string',
                'max:5000',
            ],
            'internal_note' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(TenantDocumentStatus::class)],
            'tenant_visible' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
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
            'tenant_id',
            'property_id',
            'related_type',
            'related_id',
            'document_type',
            'title',
            'description_for_tenant',
            'internal_note',
            'status',
            'tenant_visible',
            'expires_at',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'tenant_id',
            'property_id',
            'related_type',
            'related_id',
            'document_type',
            'title',
            'description_for_tenant',
            'internal_note',
            'status',
            'expires_at',
        ]);

        $this->emptyStringsToNull([
            'property_id',
            'related_type',
            'related_id',
            'description_for_tenant',
            'internal_note',
            'expires_at',
        ]);

        $this->castBooleans(['tenant_visible']);
    }
}
