<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ProjectTeamRole: string implements HasLabel
{
    use HasTranslatedLabel;

    case MANAGER = 'manager';
    case CONTRIBUTOR = 'contributor';
    case OBSERVER = 'observer';
}
