<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantKycProfileStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case NOT_STARTED = 'not_started';
    case INCOMPLETE = 'incomplete';
    case PENDING_REVIEW = 'pending_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case DISABLED = 'disabled';
}
