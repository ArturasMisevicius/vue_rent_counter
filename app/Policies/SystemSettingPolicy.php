<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class SystemSettingPolicy
{
    use AuthorizesSuperadminOnly;
}
