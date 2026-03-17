<?php

namespace App\Enums;

enum SubscriptionAccessMode: string
{
    case ACTIVE = 'active';
    case LIMIT_BLOCKED = 'limit_blocked';
    case GRACE_READ_ONLY = 'grace_read_only';
    case POST_GRACE_READ_ONLY = 'post_grace_read_only';
}
