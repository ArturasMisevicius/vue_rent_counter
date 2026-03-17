<?php

it('returns not found for removed public debug entrypoints', function (string $path): void {
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
]);

it('returns not found for the removed test debug route', function (): void {
    $this->get('/test-debug')->assertNotFound();
});

it('keeps index.php as the only public php entrypoint', function (): void {
    $publicPhpFiles = collect(glob(public_path('*.php')) ?: [])
        ->map(fn (string $path): string => basename($path))
        ->sort()
        ->values()
        ->all();

    expect($publicPhpFiles)
        ->toHaveCount(1)
        ->toBe(['index.php']);
});
