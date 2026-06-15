<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantKycDocumentType: string implements HasLabel
{
    use HasTranslatedLabel;

    case IDENTITY_CARD = 'identity_card';
    case PASSPORT = 'passport';
    case DRIVERS_LICENSE = 'drivers_license';
    case RESIDENCE_PERMIT = 'residence_permit';
    case ADDRESS_PROOF = 'address_proof';
    case RENTAL_CONTRACT = 'rental_contract';
    case OTHER = 'other';

    public function tenantDocumentType(): TenantDocumentType
    {
        return match ($this) {
            self::IDENTITY_CARD,
            self::PASSPORT,
            self::DRIVERS_LICENSE,
            self::RESIDENCE_PERMIT => TenantDocumentType::KYC_IDENTITY,
            self::ADDRESS_PROOF,
            self::RENTAL_CONTRACT,
            self::OTHER => TenantDocumentType::KYC_ADDRESS,
        };
    }
}
