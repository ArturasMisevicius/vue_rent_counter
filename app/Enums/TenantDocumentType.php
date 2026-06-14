<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantDocumentType: string implements HasLabel
{
    use HasTranslatedLabel;

    case INVOICE = 'invoice';
    case INVOICE_PDF = 'invoice_pdf';
    case RENTAL_CONTRACT = 'rental_contract';
    case CONTRACT_APPENDIX = 'contract_appendix';
    case KYC_IDENTITY = 'kyc_identity';
    case KYC_ADDRESS = 'kyc_address';
    case PAYMENT_RECEIPT = 'payment_receipt';
    case PROPERTY_DOCUMENT = 'property_document';
    case METER_PHOTO = 'meter_photo';
    case EXTRA_CHARGE_ATTACHMENT = 'extra_charge_attachment';
    case REPAIR_RECEIPT = 'repair_receipt';
    case PROVIDER_INVOICE = 'provider_invoice';
    case OTHER = 'other';

    public function isKyc(): bool
    {
        return in_array($this, [self::KYC_IDENTITY, self::KYC_ADDRESS], true);
    }

    public function isRentalContract(): bool
    {
        return $this === self::RENTAL_CONTRACT;
    }
}
