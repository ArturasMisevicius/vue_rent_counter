<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class OrganizationUserPolicy
{
    use AuthorizesSuperadminOnly;
}
