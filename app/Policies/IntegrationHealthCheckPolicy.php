<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class IntegrationHealthCheckPolicy
{
    use AuthorizesSuperadminOnly;
}
