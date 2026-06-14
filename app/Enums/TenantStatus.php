<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case MOVE_OUT_SCHEDULED = 'move_out_scheduled';
    case INACTIVE = 'inactive';
    case MOVED_OUT = 'moved_out';
    case ARCHIVED = 'archived';
}
