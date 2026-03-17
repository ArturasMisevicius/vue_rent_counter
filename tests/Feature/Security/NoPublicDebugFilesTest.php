<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('returns not found for removed public debug entrypoints and routes', function (string $path): void {
    $this->get($path)->assertNotFound();
})->with([
    '/check-logs.php',
    '/debug-auth.php',
    '/get-error.php',
    '/superadmin-setup-test.php',
    '/test-login.php',
    '/test-superadmin-login.php',
    '/test-superadmin-panel.php',
    '/test-superadmin-performance.php',
    '/test.php',
    '/index_fixed.php',
    '/sw.js',
    '/test-debug',
]);

it('keeps index.php as the only public php entrypoint', function (): void {
    $publicPhpFiles = collect(File::files(public_path()))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
        ->sort()
        ->values()
        ->all();

    expect($publicPhpFiles)
        ->toHaveCount(1)
        ->toBe(['index.php']);
});
