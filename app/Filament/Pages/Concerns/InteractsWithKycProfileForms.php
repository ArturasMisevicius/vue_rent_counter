<?php

declare(strict_types=1);

namespace App\Filament\Pages\Concerns;

use App\Filament\Actions\Profile\UpsertKycProfileAction;
use App\Http\Requests\Profile\UpsertKycProfileRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

trait InteractsWithKycProfileForms
{
    use WithFileUploads;

    public array $kycForm = [];

    abstract protected function user(): User;

    protected function fillKycProfileForm(): void
    {
        $profile = $this->user()->kycProfile;

        $this->kycForm = [
            'full_legal_name' => $profile?->full_legal_name,
            'birth_date' => $profile?->birth_date?->toDateString(),
            'nationality' => $profile?->nationality,
            'gender' => $profile?->gender,
            'marital_status' => $profile?->marital_status,
            'tax_id_number' => $profile?->tax_id_number,
            'social_security_number' => $profile?->social_security_number,
            'facial_recognition_consent' => $profile?->facial_recognition_consent ?? false,
            'secondary_contact_name' => $profile?->secondary_contact_name,
            'secondary_contact_relationship' => $profile?->secondary_contact_relationship,
            'secondary_contact_phone' => $profile?->secondary_contact_phone,
            'secondary_contact_email' => $profile?->secondary_contact_email,
            'tertiary_contact_name' => $profile?->tertiary_contact_name,
            'tertiary_contact_relationship' => $profile?->tertiary_contact_relationship,
            'tertiary_contact_phone' => $profile?->tertiary_contact_phone,
            'tertiary_contact_email' => $profile?->tertiary_contact_email,
            'employer_name' => $profile?->employer_name,
            'employment_position' => $profile?->employment_position,
            'employment_contract_type' => $profile?->employment_contract_type,
            'monthly_income_range' => $profile?->monthly_income_range,
            'iban' => $profile?->iban,
            'swift_bic' => $profile?->swift_bic,
            'bank_name' => $profile?->bank_name,
            'bank_account_holder_name' => $profile?->bank_account_holder_name,
            'payment_history_score' => $profile?->payment_history_score,
            'external_credit_bureau_reference' => $profile?->external_credit_bureau_reference,
            'internal_credit_score' => $profile?->internal_credit_score,
            'blacklist_status' => $profile?->blacklist_status ?? false,
            'profile_photo' => null,
            'passport_scan' => null,
            'national_id_front' => null,
            'national_id_back' => null,
            'drivers_license' => null,
            'employment_verification_letter' => null,
            'direct_debit_mandate' => null,
        ];
    }

    protected function persistKycProfile(UpsertKycProfileAction $upsertKycProfileAction): bool
    {
        if (! $this->hasKycPayload() && $this->user()->kycProfile === null) {
            return true;
        }

        $attributes = $this->validateKycProfileForm();

        if ($attributes === null) {
            return false;
        }

        $upsertKycProfileAction->handle($this->user(), $attributes);
        $this->fillKycProfileForm();

        return true;
    }

    private function hasKycPayload(): bool
    {
        foreach ($this->kycForm as $value) {
            if ($value instanceof UploadedFile) {
                return true;
            }

            if (is_bool($value)) {
                if ($value) {
                    return true;
                }

                continue;
            }

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    private function validateKycProfileForm(): ?array
    {
        try {
            $request = new UpsertKycProfileRequest;

            return $request->validatePayload($this->kycForm, $this->user());
        } catch (ValidationException $exception) {
            $this->addPrefixedValidationErrors('kycForm', $exception);

            return null;
        }
    }
}
