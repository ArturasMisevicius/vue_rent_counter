<?php

namespace App\Enums;

enum MeterReadingSubmissionMethod: string
{
    case ADMIN_MANUAL = 'admin_manual';
    case TENANT_PORTAL = 'tenant_portal';
    case IMPORT = 'import';
}
