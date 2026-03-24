<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class TagPolicy
{
    use AuthorizesSuperadminOnly;
}
