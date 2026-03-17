<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class UserPolicy
{
    use AuthorizesSuperadminOnly;
}
