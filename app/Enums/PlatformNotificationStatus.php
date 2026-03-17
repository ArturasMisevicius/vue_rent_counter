<?php

namespace App\Enums;

enum PlatformNotificationStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case SENT = 'sent';
    case FAILED = 'failed';
}
