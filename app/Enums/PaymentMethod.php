<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case BANK_TRANSFER = 'bank_transfer';
    case DIRECT_DEBIT = 'direct_debit';
    case CARD = 'card';
    case DIGITAL_WALLET = 'digital_wallet';
    case CHEQUE = 'cheque';
    case CASH = 'cash';
    case OTHER = 'other';
}
