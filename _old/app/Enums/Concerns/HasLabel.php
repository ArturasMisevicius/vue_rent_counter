<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

trait HasLabel
{
    abstract public function getLabel(): string;

    public function label(): string
    {
        return $this->getLabel();
    }
}