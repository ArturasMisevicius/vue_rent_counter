<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ProjectStatus;
use RuntimeException;

final class InvalidProjectTransitionException extends RuntimeException
{
    public static function between(ProjectStatus $from, ProjectStatus $to): self
    {
        return new self(sprintf('Cannot transition project from [%s] to [%s].', $from->value, $to->value));
    }
}
