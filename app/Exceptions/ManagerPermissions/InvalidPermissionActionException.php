<?php

namespace App\Exceptions\ManagerPermissions;

use InvalidArgumentException;

class InvalidPermissionActionException extends InvalidArgumentException
{
    public static function unknown(string $action): self
    {
        return new self("Unknown manager permission action [{$action}].");
    }
}
