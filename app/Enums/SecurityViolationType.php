<?php

namespace App\Enums;

enum SecurityViolationType: string
{
    case AUTHENTICATION = 'authentication';
    case RATE_LIMIT = 'rate_limit';
    case SUSPICIOUS_IP = 'suspicious_ip';
    case DATA_ACCESS = 'data_access';
}
