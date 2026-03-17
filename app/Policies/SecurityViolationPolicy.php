<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class SecurityViolationPolicy
{
    use AuthorizesSuperadminOnly;
}
