<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class PropertyAssignmentPolicy
{
    use AuthorizesSuperadminOnly;
}
