<?php

namespace App\Enums;

enum SecurityViolationType: string
{
    case SUSPICIOUS_LOGIN = 'suspicious_login';
    case REPEATED_FAILED_LOGIN = 'repeated_failed_login';
    case POLICY_VIOLATION = 'policy_violation';
    case BLOCKED_REQUEST = 'blocked_request';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->headline()->value();
    }
}
