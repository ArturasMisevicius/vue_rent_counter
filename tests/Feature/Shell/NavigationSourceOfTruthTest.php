<?php

declare(strict_types=1);

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Shell\Navigation\NavigationBuilder;
use App\Models\ManagerPermission;
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

it('keeps every configured role navigation route reachable for authorized roles', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    ['tenant' => $tenant] = createTenantInOrg($admin);

    $superadmin = User::factory()->superadmin()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    ManagerPermission::syncForManager(
        $manager,
        $organization,
        ManagerPermissionCatalog::presets()['full_access']['matrix'],
    );

    foreach ([$superadmin, $admin, $manager, $tenant] as $user) {
        $this->actingAs($user);

        foreach (navigationRouteNamesFor($user) as $routeName) {
            $this->followingRedirects()
                ->get(route($routeName))
                ->assertSuccessful();
        }
    }
});

it('renders configured role navigation labels in Lithuanian without unresolved keys', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    ['tenant' => $tenant] = createTenantInOrg($admin);

    $superadmin = User::factory()->superadmin()->create([
        'locale' => 'lt',
    ]);
    $admin->forceFill(['locale' => 'lt'])->save();
    $tenant->forceFill(['locale' => 'lt'])->save();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'locale' => 'lt',
    ]);

    ManagerPermission::syncForManager(
        $manager,
        $organization,
        ManagerPermissionCatalog::presets()['full_access']['matrix'],
    );

    app()->setLocale('lt');

    foreach ([$superadmin, $admin->fresh(), $manager, $tenant->fresh()] as $user) {
        $request = Request::create(route('filament.admin.pages.dashboard'));
        $groups = app(NavigationBuilder::class)->forUser($user, $request);

        expect($groups)->not->toBeEmpty();

        foreach ($groups as $group) {
            expect($group->label)->not->toContain('shell.navigation');

            foreach ($group->items as $item) {
                expect($item->label)->not->toContain('shell.navigation')
                    ->and($item->label)->not->toContain('admin.')
                    ->and($item->label)->not->toContain('tenant.')
                    ->and($item->label)->not->toContain('superadmin.');
            }
        }
    }
});

/**
 * @return list<string>
 */
function navigationRouteNamesFor(User $user): array
{
    $request = Request::create(route('filament.admin.pages.dashboard'));

    return collect(app(NavigationBuilder::class)->forUser($user, $request))
        ->flatMap(fn ($group): array => $group->items)
        ->map(fn ($item): string => $item->routeName)
        ->unique()
        ->values()
        ->all();
}
