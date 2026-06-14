<?php

declare(strict_types=1);

namespace App\Filament\Support\Tenants;

use App\Models\Attachment;
use Filament\Support\Icons\Heroicon;

class TenantLeaseAgreement
{
    public const FIELD = 'lease_agreement';

    public const FILE_NAMES_FIELD = 'lease_agreement_file_names';

    public const DOCUMENT_TYPE = 'lease_agreement';

    public const DISK = 'local';

    public const DIRECTORY = 'tenant-lease-agreements';

    public const MAX_SIZE_KB = 10240;

    /**
     * @return list<string>
     */
    public static function acceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    public static function fileNamesStatePath(): string
    {
        return self::FILE_NAMES_FIELD.'.'.self::FIELD;
    }

    public static function iconForAttachment(?Attachment $attachment): Heroicon
    {
        $extension = strtolower((string) pathinfo((string) ($attachment?->original_filename ?: $attachment?->filename), PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => Heroicon::OutlinedDocumentText,
            'doc', 'docx' => Heroicon::OutlinedDocument,
            default => Heroicon::OutlinedPaperClip,
        };
    }
}
