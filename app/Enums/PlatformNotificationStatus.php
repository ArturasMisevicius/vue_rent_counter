<?php

namespace App\Enums;

enum PlatformNotificationStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';

    public function label(): string
    {
        return str($this->value)->headline()->value();
    }
}
