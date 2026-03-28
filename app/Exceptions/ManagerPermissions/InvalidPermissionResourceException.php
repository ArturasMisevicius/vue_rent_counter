<?php

namespace App\Exceptions\ManagerPermissions;

use InvalidArgumentException;

class InvalidPermissionResourceException extends InvalidArgumentException
{
    public static function unknown(string $resource): self
    {
        return new self("Unknown manager permission resource [{$resource}].");
    }
}
