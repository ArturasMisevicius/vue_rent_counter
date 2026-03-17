<?php

namespace App\Enums;

enum AuditLogAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case SUSPENDED = 'suspended';
    case REINSTATED = 'reinstated';
    case EXTENDED = 'extended';
    case UPGRADED = 'upgraded';
    case CANCELLED = 'cancelled';
    case SENT = 'sent';
    case BLOCKED = 'blocked';
    case UNBLOCKED = 'unblocked';
    case IMPORTED = 'imported';
    case EXPORTED = 'exported';
    case CHECKED = 'checked';
    case RESET = 'reset';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->headline()->value();
    }
}
