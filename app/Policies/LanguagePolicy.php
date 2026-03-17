<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesSuperadminOnly;

class LanguagePolicy
{
    use AuthorizesSuperadminOnly;
}
