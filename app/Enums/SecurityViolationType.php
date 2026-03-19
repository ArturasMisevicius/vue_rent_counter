<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SecurityViolationType: string implements HasLabel
{
    use HasTranslatedLabel;

    case AUTHENTICATION = 'authentication';
    case AUTHORIZATION = 'authorization';
    case RATE_LIMIT = 'rate_limit';
    case INJECTION = 'injection';
    case CSP = 'csp';
    case SUSPICIOUS_IP = 'suspicious_ip';
    case IMPERSONATION = 'impersonation';
    case TENANT_ISOLATION = 'tenant_isolation';
    case DATA_ACCESS = 'data_access';
    case DATA_EXPORT = 'data_export';
    case SESSION_HIJACK = 'session_hijack';
}
