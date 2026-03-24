<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prints release-readiness evidence for the milestone', function () {
    $this->artisan('ops:release-readiness')
        ->expectsOutputToContain('Release Readiness Evidence')
        ->expectsOutputToContain('Integration health')
        ->expectsOutputToContain('Backup and restore')
        ->expectsOutputToContain('Queue worker')
        ->expectsOutputToContain('Manual checks')
        ->assertExitCode(0);
});

it('documents the release-readiness checklist with executable commands', function () {
    $path = base_path('docs/operations/release-readiness.md');

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))
        ->toContain('php artisan ops:release-readiness')
        ->toContain('php artisan ops:backup-restore-readiness')
        ->toContain('php artisan queue:work')
        ->toContain('/app/integration-health');
});
