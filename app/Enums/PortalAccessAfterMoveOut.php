<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PortalAccessAfterMoveOut: string implements HasLabel
{
    use HasTranslatedLabel;

    case KEEP_HISTORICAL_ACCESS = 'keep_historical_access';
    case DISABLE_IMMEDIATELY = 'disable_immediately';
    case DISABLE_AFTER_FINAL_INVOICE_PAID = 'disable_after_final_invoice_paid';
    case DISABLE_AFTER_RETENTION_DAYS = 'disable_after_retention_days';
}
