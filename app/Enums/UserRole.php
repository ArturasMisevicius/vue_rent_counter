<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => __('shell.roles.superadmin'),
            self::ADMIN => __('shell.roles.admin'),
            self::MANAGER => __('shell.roles.manager'),
            self::TENANT => __('shell.roles.tenant'),
        };
    }
}
