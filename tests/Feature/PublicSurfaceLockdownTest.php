<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('keeps only index.php as the public php entrypoint', function (): void {
    $publicPhpFiles = collect(glob(public_path('*.php')) ?: [])
        ->map(fn (string $path): string => basename($path))
        ->sort()
        ->values()
        ->all();

    expect($publicPhpFiles)
        ->toHaveCount(1)
        ->toBe(['index.php']);
});

it('returns not found for removed or quarantined public diagnostics and debug routes', function (string $path): void {
    $this->get($path)->assertNotFound();
})->with([
    '/test-debug',
    '/translation-test.php',
    '/translation-test',
    '/swap.php',
    '/swap',
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

it('retains tenant and auth route coverage after public surface lockdown', function (): void {
    $this->get(route('home'))->assertSuccessful();
    $this->get(route('login'))->assertSuccessful();
    $this->get(route('register'))->assertSuccessful();

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful();

    expect(app('router')->getRoutes()->getByName('tenant.invoices.download'))->not->toBeNull();
});

it('does not serve debug artifacts from filesystem checks', function (): void {
    $forbiddenFiles = [
        'test-debug.php',
        'translation-test.php',
        'swap.php',
        'sw.js',
    ];

    $publicFiles = collect(File::files(public_path()))
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
        ->values()
        ->all();

    expect($publicFiles)->not->toContain(...$forbiddenFiles);
});
