<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum OrganizationStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
    case ARCHIVED = 'archived';

    public function badgeColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PENDING => 'warning',
            self::SUSPENDED, self::CANCELLED => 'danger',
            self::ARCHIVED => 'gray',
        };
    }

    public function permitsAccess(): bool
    {
        return $this === self::ACTIVE;
    }
}
