<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    use HasTranslatedLabel;

    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';

    /**
     * @return array<int, string>
     */
    public static function adminLikeValues(): array
    {
        return self::onlyValues(
            self::SUPERADMIN,
            self::ADMIN,
            self::MANAGER,
        );
    }
}
