<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ExtraChargeStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case INCLUDED_IN_INVOICE = 'included_in_invoice';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case VOIDED = 'voided';

    /**
     * @return array<int, string>
     */
    public static function invoiceableValues(): array
    {
        return self::onlyValues(
            self::APPROVED,
            self::INCLUDED_IN_INVOICE,
        );
    }

    public function affectsInvoiceTotals(): bool
    {
        return in_array($this, [
            self::APPROVED,
            self::INCLUDED_IN_INVOICE,
        ], true);
    }
}
