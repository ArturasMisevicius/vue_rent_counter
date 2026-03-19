<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum AuditLogAction: string implements HasLabel
{
    use HasTranslatedLabel;

    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case EXPORTED = 'exported';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case RESTORED = 'restored';
    case SUSPENDED = 'suspended';
    case REINSTATED = 'reinstated';
    case IMPERSONATED = 'impersonated';
    case LOGGED_IN = 'logged_in';
    case LOGGED_OUT = 'logged_out';
    case SENT = 'sent';
}
