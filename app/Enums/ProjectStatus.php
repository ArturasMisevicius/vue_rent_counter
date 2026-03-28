<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ProjectStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function badgeColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PLANNED => 'info',
            self::IN_PROGRESS => 'warning',
            self::ON_HOLD => 'danger',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT => in_array($next, [self::PLANNED, self::CANCELLED], true),
            self::PLANNED => in_array($next, [self::IN_PROGRESS, self::ON_HOLD, self::CANCELLED], true),
            self::IN_PROGRESS => in_array($next, [self::ON_HOLD, self::COMPLETED, self::CANCELLED], true),
            self::ON_HOLD => in_array($next, [self::IN_PROGRESS, self::CANCELLED], true),
            self::COMPLETED, self::CANCELLED => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED], true);
    }
}
