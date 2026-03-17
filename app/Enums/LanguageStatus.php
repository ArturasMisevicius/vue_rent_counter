<?php

namespace App\Enums;

enum LanguageStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return str($this->value)->headline()->value();
    }
}
