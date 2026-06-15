<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Enums\TenantKycDocumentType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'billing_contact_email' => ['nullable', 'email', 'max:255'],
            'invoice_footer' => ['nullable', 'string'],
            'kyc_required' => ['required', 'boolean'],
            'required_document_types' => ['nullable', 'array'],
            'required_document_types.*' => [Rule::enum(TenantKycDocumentType::class)],
            'require_expiry_date' => ['required', 'boolean'],
            'block_portal_until_verified' => ['required', 'boolean'],
            'block_invoice_download_until_verified' => ['required', 'boolean'],
            'block_reading_submission_until_verified' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'organization_name.required' => ['required', 'organization_name'],
            'organization_name.max' => ['max.string', 'organization_name', ['max' => 255]],
            'billing_contact_email.email' => ['email', 'billing_contact_email'],
            'billing_contact_email.max' => ['max.string', 'billing_contact_email', ['max' => 255]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'organization_name',
            'billing_contact_email',
            'invoice_footer',
            'kyc_required',
            'required_document_types',
            'require_expiry_date',
            'block_portal_until_verified',
            'block_invoice_download_until_verified',
            'block_reading_submission_until_verified',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'organization_name',
            'billing_contact_email',
            'invoice_footer',
            'required_document_types',
        ]);

        $this->emptyStringsToNull([
            'billing_contact_email',
            'invoice_footer',
        ]);

        $this->castBooleans([
            'kyc_required',
            'require_expiry_date',
            'block_portal_until_verified',
            'block_invoice_download_until_verified',
            'block_reading_submission_until_verified',
        ]);
    }
}
