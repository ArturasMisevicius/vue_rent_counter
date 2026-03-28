<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ProjectCostRecordType: string implements HasLabel
{
    use HasTranslatedLabel;

    case MATERIALS = 'materials';
    case CONTRACTOR_FEE = 'contractor_fee';
    case PERMIT = 'permit';
    case EXPENSE = 'expense';
    case OTHER = 'other';
}
