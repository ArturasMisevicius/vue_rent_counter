<?php

declare(strict_types=1);

namespace App\Support\Logging\Tap;

use App\Support\Logging\RedactSensitiveData;
use Illuminate\Log\Logger;

final class RedactSensitiveDataTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (method_exists($monolog, 'pushProcessor')) {
            $monolog->pushProcessor(new RedactSensitiveData);
        }
    }
}
