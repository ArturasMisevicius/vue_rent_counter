<?php

declare(strict_types=1);

namespace App\Http\Requests\TenantKyc;

use App\Enums\TenantKycDocumentType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitTenantKycDocumentRequest extends FormRequest
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
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('organization_id', $this->organizationId)),
            ],
            'document_type' => ['required', Rule::enum(TenantKycDocumentType::class)],
            'document_number_encrypted' => ['nullable', 'string', 'max:255'],
            'issued_country' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'internal_note' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'organization_id',
            'tenant_id',
            'document_type',
            'document_number_encrypted',
            'issued_country',
            'issued_at',
            'expires_at',
            'internal_note',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'organization_id',
            'tenant_id',
            'document_type',
            'document_number_encrypted',
            'issued_country',
            'issued_at',
            'expires_at',
            'internal_note',
        ]);

        $this->emptyStringsToNull([
            'document_number_encrypted',
            'issued_country',
            'issued_at',
            'expires_at',
            'internal_note',
        ]);
    }
}
