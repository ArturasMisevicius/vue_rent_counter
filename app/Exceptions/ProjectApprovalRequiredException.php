<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ProjectApprovalRequiredException extends RuntimeException
{
    public static function forStart(): self
    {
        return new self('Project approval is required before work can start.');
    }
}
