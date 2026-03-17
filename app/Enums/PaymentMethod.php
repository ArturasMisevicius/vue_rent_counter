<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case CASH = 'cash';
    case OTHER = 'other';
}
