<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class TaskPolicy
{
    use AuthorizesSuperadminOnly;
}
