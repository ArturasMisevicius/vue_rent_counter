<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';

    /**
     * @return array<int, string>
     */
    public static function outstandingValues(): array
    {
        return self::onlyValues(
            self::FINALIZED,
            self::PARTIALLY_PAID,
            self::OVERDUE,
        );
    }

    /**
     * @return array<int, string>
     */
    public static function pendingAttentionValues(): array
    {
        return self::onlyValues(
            self::DRAFT,
            self::FINALIZED,
            self::PARTIALLY_PAID,
            self::OVERDUE,
        );
    }
}
