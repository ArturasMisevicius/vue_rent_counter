<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prints a runnable backup and restore readiness report', function () {
    $this->artisan('ops:backup-restore-readiness')
        ->expectsOutputToContain('Backup and Restore Readiness')
        ->expectsOutputToContain('Database connection')
        ->expectsOutputToContain('Backup directory')
        ->expectsOutputToContain('Restore staging directory')
        ->expectsOutputToContain('Result: READY')
        ->assertExitCode(0);

    expect(is_dir(storage_path('app/operations/backups')))->toBeTrue()
        ->and(is_dir(storage_path('app/operations/restore')))->toBeTrue();
});

it('documents the backup and restore readiness workflow', function () {
    $path = base_path('docs/operations/backup-restore.md');

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))
        ->toContain('php artisan ops:backup-restore-readiness')
        ->toContain('storage/app/operations/backups')
        ->toContain('storage/app/operations/restore');
});
