<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ProjectCostPassthroughException extends RuntimeException
{
    public static function invalidState(): self
    {
        return new self('Project cost passthrough can only be generated for completed passthrough-enabled projects.');
    }
}
