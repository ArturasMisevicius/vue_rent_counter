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
            'billing_contact_name' => ['nullable', 'string', 'max:255'],
            'billing_contact_email' => ['nullable', 'email', 'max:255'],
            'billing_contact_phone' => ['nullable', 'string', 'max:255'],
            'payment_instructions' => ['nullable', 'string'],
            'invoice_footer' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'billing_contact_email.email' => ['email', 'billing_contact_email'],
            'billing_contact_name.max' => ['max.string', 'billing_contact_name', ['max' => 255]],
            'billing_contact_email.max' => ['max.string', 'billing_contact_email', ['max' => 255]],
            'billing_contact_phone.max' => ['max.string', 'billing_contact_phone', ['max' => 255]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'billing_contact_name',
            'billing_contact_email',
            'billing_contact_phone',
            'payment_instructions',
            'invoice_footer',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'billing_contact_name',
            'billing_contact_email',
            'billing_contact_phone',
            'payment_instructions',
            'invoice_footer',
        ]);

        $this->emptyStringsToNull([
            'billing_contact_name',
            'billing_contact_email',
            'billing_contact_phone',
            'payment_instructions',
            'invoice_footer',
        ]);
    }
}
