<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ProjectDeletionBlockedException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
