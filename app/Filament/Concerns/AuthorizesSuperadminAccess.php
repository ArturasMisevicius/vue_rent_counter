<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

trait AuthorizesSuperadminAccess
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
