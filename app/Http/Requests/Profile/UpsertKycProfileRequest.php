<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpsertKycProfileRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'full_legal_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'tax_id_number' => ['nullable', 'string', 'max:255'],
            'social_security_number' => ['nullable', 'string', 'max:255'],
            'facial_recognition_consent' => ['boolean'],
            'secondary_contact_name' => ['nullable', 'string', 'max:255'],
            'secondary_contact_relationship' => ['nullable', 'string', 'max:255'],
            'secondary_contact_phone' => ['nullable', 'string', 'max:255'],
            'secondary_contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'tertiary_contact_name' => ['nullable', 'string', 'max:255'],
            'tertiary_contact_relationship' => ['nullable', 'string', 'max:255'],
            'tertiary_contact_phone' => ['nullable', 'string', 'max:255'],
            'tertiary_contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'employment_position' => ['nullable', 'string', 'max:255'],
            'employment_contract_type' => ['nullable', 'string', 'max:255'],
            'monthly_income_range' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'swift_bic' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'payment_history_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'external_credit_bureau_reference' => ['nullable', 'string', 'max:255'],
            'internal_credit_score' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'blacklist_status' => ['boolean'],
            'profile_photo' => ['nullable', 'image', 'max:10240'],
            'passport_scan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'national_id_front' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'national_id_back' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'drivers_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'employment_verification_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'direct_debit_mandate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'full_legal_name',
            'nationality',
            'gender',
            'marital_status',
            'tax_id_number',
            'social_security_number',
            'secondary_contact_name',
            'secondary_contact_relationship',
            'secondary_contact_phone',
            'secondary_contact_email',
            'tertiary_contact_name',
            'tertiary_contact_relationship',
            'tertiary_contact_phone',
            'tertiary_contact_email',
            'employer_name',
            'employment_position',
            'employment_contract_type',
            'monthly_income_range',
            'iban',
            'swift_bic',
            'bank_name',
            'bank_account_holder_name',
            'external_credit_bureau_reference',
        ]);

        $this->emptyStringsToNull([
            'full_legal_name',
            'birth_date',
            'nationality',
            'gender',
            'marital_status',
            'tax_id_number',
            'social_security_number',
            'secondary_contact_name',
            'secondary_contact_relationship',
            'secondary_contact_phone',
            'secondary_contact_email',
            'tertiary_contact_name',
            'tertiary_contact_relationship',
            'tertiary_contact_phone',
            'tertiary_contact_email',
            'employer_name',
            'employment_position',
            'employment_contract_type',
            'monthly_income_range',
            'iban',
            'swift_bic',
            'bank_name',
            'bank_account_holder_name',
            'payment_history_score',
            'external_credit_bureau_reference',
            'internal_credit_score',
        ]);

        $this->castBooleans([
            'facial_recognition_consent',
            'blacklist_status',
        ]);
    }
}
