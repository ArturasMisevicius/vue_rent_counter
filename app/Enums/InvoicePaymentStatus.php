<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum InvoicePaymentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case OVERPAID = 'overpaid';
    case CANCELLED = 'cancelled';
    case VOIDED = 'voided';
    case REFUNDED = 'refunded';

    /**
     * @return array<int, string>
     */
    public static function openBalanceValues(): array
    {
        return self::onlyValues(
            self::UNPAID,
            self::PARTIALLY_PAID,
            self::OVERDUE,
        );
    }
}
