<?php

namespace App\Filament\Support\RentalContracts;

class RentalContractFile
{
    public const FIELD = 'contract_file';

    public const DOCUMENT_TYPE = 'rental_contract';

    public const DISK = 'local';

    public const DIRECTORY = 'rental-contracts';

    public const MAX_SIZE_KB = 10240;

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
        ];
    }
}
