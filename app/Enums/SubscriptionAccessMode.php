<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionAccessMode: string implements HasLabel
{
    use HasTranslatedLabel;

    case ACTIVE = 'active';
    case LIMIT_BLOCKED = 'limit_blocked';
    case GRACE_READ_ONLY = 'grace_read_only';
    case POST_GRACE_READ_ONLY = 'post_grace_read_only';
}
