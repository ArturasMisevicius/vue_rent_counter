<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function relativeMatchingFiles(string $directory, callable $predicate): array
{
    if (! File::isDirectory(base_path($directory))) {
        return [];
    }

    return collect(File::allFiles(base_path($directory)))
        ->map(fn (SplFileInfo $file): string => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()))
        ->filter($predicate)
        ->sort()
        ->values()
        ->all();
}

function bladeFilesContaining(string $needle): array
{
    return relativeMatchingFiles('resources/views', function (string $path) use ($needle): bool {
        if (! Str::endsWith($path, '.blade.php')) {
            return false;
        }

        return str_contains((string) file_get_contents(base_path($path)), $needle);
    });
}

function phpFilesMatching(array $directories, string $pattern): array
{
    return collect($directories)
        ->flatMap(fn (string $directory): array => relativeMatchingFiles($directory, function (string $path) use ($pattern): bool {
            if (! Str::endsWith($path, '.php')) {
                return false;
            }

            return preg_match($pattern, (string) file_get_contents(base_path($path))) === 1;
        }))
        ->sort()
        ->values()
        ->all();
}

it('keeps tenant layout references fully removed', function (): void {
    expect(bladeFilesContaining(sprintf('layouts.%s', 'tenant')))->toBe([]);
});

it('keeps superadmin layout references fully removed', function (): void {
    expect(bladeFilesContaining(sprintf('layouts.%s', 'superadmin')))->toBe([]);
});

it('keeps role-scoped component files fully removed', function (): void {
    expect(relativeMatchingFiles('resources/views/components/backoffice', fn (string $path): bool => Str::endsWith($path, '.blade.php')))
        ->toBe([]);

    expect(relativeMatchingFiles('resources/views/components/manager', fn (string $path): bool => Str::endsWith($path, '.blade.php')))
        ->toBe([]);

    expect(relativeMatchingFiles('resources/views/components/tenant', fn (string $path): bool => Str::endsWith($path, '.blade.php')))
        ->toBe([]);
});

it('does not add new inline livewire validation beyond the approved migration baseline', function (): void {
    expect(phpFilesMatching(['app/Livewire'], '/\\$this->validate\\(/'))
        ->toBe([
            'app/Livewire/Manager/MeterReadingForm.php',
        ]);
});

it('does not add new inline controller or route validation beyond the approved migration baseline', function (): void {
    expect(phpFilesMatching(
        ['app/Http/Controllers', 'routes'],
        '/\\$request->validate\\(|request\\(\\)->validate\\(/',
    ))->toBe([
        'app/Http/Controllers/Admin/PropertyController.php',
        'app/Http/Controllers/Enhanced/InvoiceController.php',
        'app/Http/Controllers/InvitationAcceptanceController.php',
        'app/Http/Controllers/InvoiceController.php',
        'app/Http/Controllers/Superadmin/DashboardController.php',
        'app/Http/Controllers/Superadmin/ImpersonationController.php',
        'app/Http/Controllers/Superadmin/InvitationController.php',
        'app/Http/Controllers/Superadmin/SubscriptionController.php',
        'app/Http/Controllers/Superadmin/UserController.php',
        'routes/web.php',
    ]);
});
