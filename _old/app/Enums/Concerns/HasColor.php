<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasColor
{
    abstract public function getColor(): string;

    public function color(): string
    {
        return $this->getColor();
    }
}