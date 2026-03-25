<?php

declare(strict_types=1);

namespace App\Filament\Support\Kyc;

use App\Models\Attachment;
use Filament\Support\Icons\Heroicon;

class KycAttachmentRegistry
{
    public const FIELDS = [
        'profile_photo' => [
            'document_type' => 'profile_photo',
            'label' => 'shell.profile.kyc.fields.profile_photo',
            'accepted_file_types' => ['image/*'],
            'is_photo' => true,
        ],
        'passport_scan' => [
            'document_type' => 'passport',
            'label' => 'shell.profile.kyc.fields.passport_scan',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
        'national_id_front' => [
            'document_type' => 'national_id_front',
            'label' => 'shell.profile.kyc.fields.national_id_front',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
        'national_id_back' => [
            'document_type' => 'national_id_back',
            'label' => 'shell.profile.kyc.fields.national_id_back',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
        'drivers_license' => [
            'document_type' => 'drivers_license',
            'label' => 'shell.profile.kyc.fields.drivers_license',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
        'employment_verification_letter' => [
            'document_type' => 'employment_verification_letter',
            'label' => 'shell.profile.kyc.fields.employment_verification_letter',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
        'direct_debit_mandate' => [
            'document_type' => 'direct_debit_mandate',
            'label' => 'shell.profile.kyc.fields.direct_debit_mandate',
            'accepted_file_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'is_photo' => false,
        ],
    ];

    public static function fields(): array
    {
        return self::FIELDS;
    }

    public static function fieldNames(): array
    {
        return array_keys(self::FIELDS);
    }

    public static function documentTypeForField(string $field): string
    {
        return self::FIELDS[$field]['document_type'];
    }

    public static function labelForField(string $field): string
    {
        return __(self::FIELDS[$field]['label']);
    }

    public static function acceptedFileTypes(string $field): array
    {
        return self::FIELDS[$field]['accepted_file_types'];
    }

    public static function isPhotoField(string $field): bool
    {
        return self::FIELDS[$field]['is_photo'];
    }

    public static function fileNamesStatePath(string $field): string
    {
        return 'attachment_file_names.'.$field;
    }

    public static function extensionForAttachment(?Attachment $attachment): ?string
    {
        if ($attachment === null) {
            return null;
        }

        $filename = $attachment->original_filename ?: $attachment->filename ?: $attachment->path;

        $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : null;
    }

    public static function iconForExtension(?string $extension): Heroicon
    {
        return match (strtolower((string) $extension)) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg' => Heroicon::OutlinedPhoto,
            'pdf' => Heroicon::OutlinedDocumentText,
            'doc', 'docx', 'odt', 'rtf' => Heroicon::OutlinedDocument,
            'xls', 'xlsx', 'csv' => Heroicon::OutlinedDocumentChartBar,
            default => Heroicon::OutlinedPaperClip,
        };
    }
}
