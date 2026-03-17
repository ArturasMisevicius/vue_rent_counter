<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class PlatformNotificationPolicy
{
    use AuthorizesSuperadminOnly;
}
