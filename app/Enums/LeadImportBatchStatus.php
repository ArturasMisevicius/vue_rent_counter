<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum LeadImportBatchStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case PREVIEWED = 'previewed';
    case IMPORTED = 'imported';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
