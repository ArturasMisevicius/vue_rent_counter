<?php

declare(strict_types=1);

namespace App\Filament\Support\TenantDocuments;

class TenantDocumentFile
{
    public const FIELD = 'document_file';

    public const DISK = 'local';

    public const DIRECTORY = 'tenant-documents';

    public const MAX_SIZE_KB = 20480;

    public static function directory(int $organizationId, int $tenantId): string
    {
        return self::DIRECTORY.'/'.$organizationId.'/'.$tenantId;
    }

    public static function fileNamesStatePath(): string
    {
        return self::FIELD.'_file_names';
    }

    /**
     * @return list<string>
     */
    public static function acceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }
}
