<?php

use Symfony\Component\Process\Process;

it('does not include testing routes in the live web route file', function (): void {
    expect(file_get_contents(base_path('routes/web.php')))
        ->not->toContain("require base_path('routes/testing.php');");
});

it('does not expose __test routes in the live route inventory', function (): void {
    $process = new Process([PHP_BINARY, 'artisan', 'route:list', '--path=__test', '--json'], base_path());

    $process->run();

    expect($process->isSuccessful())
        ->toBeTrue()
        ->and($process->getOutput().$process->getErrorOutput())
        ->toContain("doesn't have any routes matching the given criteria");
});

it('keeps shared test routes available through the Pest bootstrap only', function (): void {
    registerSharedTestRoutes();

    expect(route('test.intended'))->toEndWith('/__test/intended');

    $this->get(route('test.intended'))
        ->assertRedirect(route('login'));
});
