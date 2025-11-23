<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum TariffType: string implements HasLabel
{
    use HasTranslatableLabel;

    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';
}
