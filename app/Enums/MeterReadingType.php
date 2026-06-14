<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingType: string implements HasLabel
{
    use HasTranslatedLabel;

    case REGULAR = 'regular';
    case MOVE_OUT_FINAL = 'move_out_final';
}
