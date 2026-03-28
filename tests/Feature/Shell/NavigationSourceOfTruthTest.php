<?php

declare(strict_types=1);

use App\Filament\Support\Shell\Navigation\NavigationBuilder;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('builds sidebar navigation from the configured navigation roles', function (): void {
    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $builder = app(NavigationBuilder::class);
    $request = Request::create(route('filament.admin.pages.dashboard'));

    $superadminGroups = collect($builder->forUser($superadmin, $request))->mapWithKeys(
        fn ($group): array => [$group->key => collect($group->items)->map(fn ($item): string => $item->routeName)->all()],
    );
    $adminGroups = collect($builder->forUser($admin, $request))->mapWithKeys(
        fn ($group): array => [$group->key => collect($group->items)->map(fn ($item): string => $item->routeName)->all()],
    );
    $tenantGroups = collect($builder->forUser($tenant, $request))->mapWithKeys(
        fn ($group): array => [$group->key => collect($group->items)->map(fn ($item): string => $item->routeName)->all()],
    );

    $configuredSuperadminGroups = collect(config('tenanto.shell.navigation.roles.superadmin'))
        ->filter(fn (array $items): bool => $items !== [])
        ->map(fn (array $items): array => collect($items)->pluck('route')->all())
        ->all();
    $configuredAdminGroups = collect(config('tenanto.shell.navigation.roles.admin'))
        ->filter(fn (array $items): bool => $items !== [])
        ->map(fn (array $items): array => collect($items)->pluck('route')->all())
        ->all();
    $configuredTenantGroups = collect(config('tenanto.shell.navigation.roles.tenant'))
        ->filter(fn (array $items): bool => $items !== [])
        ->map(fn (array $items): array => collect($items)->pluck('route')->all())
        ->all();

    expect($superadminGroups->all())->toBe($configuredSuperadminGroups)
        ->and($adminGroups->all())->toBe($configuredAdminGroups)
        ->and($tenantGroups->all())->toBe($configuredTenantGroups);
});

it('keeps the panel provider delegated to the shell navigation builder instead of hardcoded route lists', function (): void {
    $contents = file_get_contents(base_path('app/Providers/Filament/AppPanelProvider.php'));

    expect($contents)->not->toBeFalse()
        ->and(Str::contains((string) $contents, 'ShellNavigationBuilder'))->toBeTrue()
        ->and(Str::contains((string) $contents, 'filament.admin.resources.organization-users.index'))->toBeFalse()
        ->and(Str::contains((string) $contents, 'filament.admin.resources.projects.index'))->toBeFalse()
        ->and(Str::contains((string) $contents, 'filament.admin.resources.tags.index'))->toBeFalse();
});
