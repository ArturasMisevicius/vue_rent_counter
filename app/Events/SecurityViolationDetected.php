<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SecurityViolation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SecurityViolationDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SecurityViolation $securityViolation,
    ) {}
}
