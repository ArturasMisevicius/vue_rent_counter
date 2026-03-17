<?php

namespace App\Enums;

enum AuditLogAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case SUSPENDED = 'suspended';
    case REINSTATED = 'reinstated';
    case SENT = 'sent';
}
