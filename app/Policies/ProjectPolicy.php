<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class ProjectPolicy
{
    use AuthorizesSuperadminOnly;
}
