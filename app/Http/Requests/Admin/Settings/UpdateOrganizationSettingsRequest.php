<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

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
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'organization_name',
            'billing_contact_email',
            'invoice_footer',
        ]);

        $this->emptyStringsToNull([
            'billing_contact_email',
            'invoice_footer',
        ]);
    }
}
