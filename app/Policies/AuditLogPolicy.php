<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class AuditLogPolicy
{
    use AuthorizesSuperadminOnly;
}
