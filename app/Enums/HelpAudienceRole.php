<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use App\Models\User;
use Filament\Support\Contracts\HasLabel;

enum HelpAudienceRole: string implements HasLabel
{
    use HasTranslatedLabel;

    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';
    case SUPERADMIN = 'superadmin';
    case ALL = 'all';

    public static function fromUser(User $user): ?self
    {
        return match (true) {
            $user->isSuperadmin() => self::SUPERADMIN,
            $user->isAdmin() => self::ADMIN,
            $user->isManager() => self::MANAGER,
            $user->isTenant() => self::TENANT,
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function visibleValuesForUser(User $user): array
    {
        $role = self::fromUser($user);

        if (! $role instanceof self) {
            return [];
        }

        return self::visibleValuesForRole($role);
    }

    /**
     * @return array<int, string>
     */
    public static function visibleValuesForRole(self $role): array
    {
        return match ($role) {
            self::SUPERADMIN => self::onlyValues(self::ALL, self::SUPERADMIN, self::ADMIN, self::MANAGER),
            self::ADMIN => self::onlyValues(self::ALL, self::ADMIN),
            self::MANAGER => self::onlyValues(self::ALL, self::MANAGER, self::ADMIN),
            self::TENANT => self::onlyValues(self::ALL, self::TENANT),
            self::ALL => self::onlyValues(self::ALL),
        };
    }
}
