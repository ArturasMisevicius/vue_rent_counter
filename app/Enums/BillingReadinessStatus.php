<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum BillingReadinessStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case READY = 'ready';
    case WARNING = 'warning';
    case BLOCKED = 'blocked';
    case NOT_CONFIGURED = 'not_configured';

    public function color(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::WARNING => 'warning',
            self::BLOCKED => 'danger',
            self::NOT_CONFIGURED => 'gray',
        };
    }

    public function blocksBilling(): bool
    {
        return in_array($this, [self::BLOCKED, self::NOT_CONFIGURED], true);
    }
}
