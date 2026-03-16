<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasLabel
{
    use HasTranslatableLabel;

    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case PAID = 'paid';
}
