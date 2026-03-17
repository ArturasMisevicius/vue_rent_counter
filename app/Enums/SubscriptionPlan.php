<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';
}
