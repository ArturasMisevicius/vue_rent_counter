<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('keeps Blade templates php-free and styling css-only', function (): void {
    $process = new Process([PHP_BINARY, base_path('scripts/check_view_hygiene.php')], base_path());
    $process->run();

    expect($process->isSuccessful())
        ->toBeTrue($process->getOutput().$process->getErrorOutput());
});
