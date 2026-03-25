<?php

declare(strict_types=1);

namespace App\Filament\Actions\Profile;

use App\Enums\KycVerificationStatus;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpsertKycProfileAction
{
    private const PROFILE_FIELDS = [
        'full_legal_name',
        'birth_date',
        'nationality',
        'gender',
        'marital_status',
        'tax_id_number',
        'social_security_number',
        'facial_recognition_consent',
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
        'blacklist_status',
    ];

    private const ADMIN_ONLY_FIELDS = [
        'payment_history_score',
        'external_credit_bureau_reference',
        'internal_credit_score',
        'blacklist_status',
    ];

    private const DOCUMENT_FIELDS = [
        'profile_photo' => 'profile_photo',
        'passport_scan' => 'passport',
        'national_id_front' => 'national_id_front',
        'national_id_back' => 'national_id_back',
        'drivers_license' => 'drivers_license',
        'employment_verification_letter' => 'employment_verification_letter',
        'direct_debit_mandate' => 'direct_debit_mandate',
    ];

    public function handle(User $user, array $attributes): ?UserKycProfile
    {
        $existingProfile = $user->kycProfile;

        if (! $this->hasPayload($attributes) && $existingProfile === null) {
            return null;
        }

        $payload = $this->payloadFor($user, $attributes, $existingProfile);

        if ($existingProfile !== null && ! $this->hasDocumentUploads($attributes) && ! $this->hasChangedPayload($existingProfile, $payload)) {
            return $existingProfile->fresh();
        }

        $profile = UserKycProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'organization_id' => $user->organization_id,
                ...$payload,
                'verification_status' => KycVerificationStatus::PENDING,
                'rejection_reason' => null,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by_user_id' => null,
            ],
        );

        foreach (self::DOCUMENT_FIELDS as $field => $documentType) {
            $file = $attributes[$field] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->storeAttachment($profile, $user, $file, $documentType);
        }

        return $profile->refresh();
    }

    private function hasPayload(array $attributes): bool
    {
        foreach ($attributes as $value) {
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

    private function hasChangedPayload(UserKycProfile $profile, array $payload): bool
    {
        foreach (self::PROFILE_FIELDS as $field) {
            $incoming = $payload[$field] ?? null;
            $current = $profile->getAttribute($field);

            if ($field === 'birth_date') {
                $current = $current?->toDateString();
            }

            if ($incoming !== $current) {
                return true;
            }
        }

        return false;
    }

    private function hasDocumentUploads(array $attributes): bool
    {
        foreach (array_keys(self::DOCUMENT_FIELDS) as $field) {
            if (($attributes[$field] ?? null) instanceof UploadedFile) {
                return true;
            }
        }

        return false;
    }

    private function payloadFor(User $user, array $attributes, ?UserKycProfile $existingProfile): array
    {
        $payload = [];

        foreach (self::PROFILE_FIELDS as $field) {
            if (! $user->isAdminLike() && in_array($field, self::ADMIN_ONLY_FIELDS, true)) {
                $payload[$field] = $field === 'blacklist_status'
                    ? (bool) ($existingProfile?->getAttribute($field) ?? false)
                    : $existingProfile?->getAttribute($field);

                continue;
            }

            $payload[$field] = match ($field) {
                'facial_recognition_consent', 'blacklist_status' => (bool) ($attributes[$field] ?? false),
                default => $attributes[$field] ?? null,
            };
        }

        return $payload;
    }

    private function storeAttachment(UserKycProfile $profile, User $user, UploadedFile $file, string $documentType): void
    {
        $attachment = $profile->attachments()->firstOrNew([
            'document_type' => $documentType,
        ]);

        if ($attachment->exists && filled($attachment->path)) {
            Storage::disk((string) $attachment->disk)->delete((string) $attachment->path);
        }

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs('kyc/'.$profile->getKey(), $filename, 'local');

        $attachment->fill([
            'organization_id' => $profile->organization_id,
            'uploaded_by_user_id' => $user->id,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'disk' => 'local',
            'path' => $path,
            'document_type' => $documentType,
            'description' => null,
            'metadata' => null,
        ]);

        $profile->attachments()->save($attachment);
    }
}
