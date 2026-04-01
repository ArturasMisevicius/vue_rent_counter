<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! Route::has('filament.admin.test.manager-permission.panel')) {
        Route::middleware(['web', 'auth', 'manager.permission:buildings,create'])
            ->get('/__test/manager-permission/panel', fn (): string => 'panel-ok')
            ->name('filament.admin.test.manager-permission.panel');
    }

    if (! Route::has('test.manager-permission.api')) {
        Route::middleware(['web', 'auth', 'manager.permission:buildings,create'])
            ->get('/__test/manager-permission/api', fn () => response()->json(['status' => 'ok']))
            ->name('test.manager-permission.api');
    }

    if (! Route::has('test.manager-permission.previous')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/manager-permission/previous', fn (): string => 'previous')
            ->name('test.manager-permission.previous');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('checks manager permissions against the request organization context', function (): void {
    ['organization' => $primaryOrganization] = createOrgWithAdmin();
    ['organization' => $secondaryOrganization, 'admin' => $secondaryAdmin] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $primaryOrganization->id,
    ]);

    OrganizationUser::factory()->create([
        'organization_id' => $secondaryOrganization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'permissions' => null,
        'joined_at' => now()->subDay(),
        'left_at' => null,
        'is_active' => true,
        'invited_by' => $secondaryAdmin->id,
    ]);

    Notification::fake();

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $secondaryOrganization,
        middlewarePermissionMatrix([
            'buildings' => ['can_create' => true],
        ]),
        $secondaryAdmin,
    );

    $organizationContext = Mockery::mock(OrganizationContext::class);
    $organizationContext->shouldReceive('currentOrganization')->andReturn($secondaryOrganization);
    app()->instance(OrganizationContext::class, $organizationContext);

    /** @var TestCase $testCase */
    $testCase = $this;

    $testCase->actingAs($manager)
        ->get(route('filament.admin.test.manager-permission.panel'))
        ->assertSuccessful()
        ->assertSeeText('panel-ok');
});

it('returns a json 403 response for denied api-style requests', function (): void {
    ['organization' => $organization] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationContext = Mockery::mock(OrganizationContext::class);
    $organizationContext->shouldReceive('currentOrganization')->andReturn($organization);
    app()->instance(OrganizationContext::class, $organizationContext);

    /** @var TestCase $testCase */
    $testCase = $this;

    $testCase->actingAs($manager)
        ->getJson(route('test.manager-permission.api'))
        ->assertForbidden()
        ->assertJson([
            'message' => __('admin.manager_permissions.forbidden'),
        ]);
});

it('redirects denied panel requests back to the previous page', function (): void {
    ['organization' => $organization] = createOrgWithAdmin();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organizationContext = Mockery::mock(OrganizationContext::class);
    $organizationContext->shouldReceive('currentOrganization')->andReturn($organization);
    app()->instance(OrganizationContext::class, $organizationContext);

    /** @var TestCase $testCase */
    $testCase = $this;

    $testCase->actingAs($manager)
        ->from(route('test.manager-permission.previous'))
        ->get(route('filament.admin.test.manager-permission.panel'))
        ->assertRedirect(route('test.manager-permission.previous'));
});

it('does not restrict admins or superadmins', function (): void {
    ['organization' => $organization, 'admin' => $ownerAdmin] = createOrgWithAdmin();
    $superadmin = User::factory()->superadmin()->create();

    $organizationContext = Mockery::mock(OrganizationContext::class);
    $organizationContext->shouldReceive('currentOrganization')->andReturn($organization);
    app()->instance(OrganizationContext::class, $organizationContext);

    /** @var TestCase $testCase */
    $testCase = $this;

    $testCase->actingAs($ownerAdmin)
        ->get(route('filament.admin.test.manager-permission.panel'))
        ->assertSuccessful()
        ->assertSeeText('panel-ok');

    $testCase->actingAs($superadmin)
        ->get(route('filament.admin.test.manager-permission.panel'))
        ->assertSuccessful()
        ->assertSeeText('panel-ok');
});

function middlewarePermissionMatrix(array $overrides = []): array
{
    $resources = [
        'buildings',
        'properties',
        'tenants',
        'meters',
        'meter_readings',
        'billing',
        'invoices',
        'tariffs',
        'providers',
        'service_configurations',
        'utility_services',
    ];

    $matrix = collect($resources)
        ->mapWithKeys(fn (string $resource): array => [
            $resource => [
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
            ],
        ])
        ->all();

    foreach ($overrides as $resource => $flags) {
        $matrix[$resource] = [
            'can_create' => $flags['can_create'] ?? false,
            'can_edit' => $flags['can_edit'] ?? false,
            'can_delete' => $flags['can_delete'] ?? false,
        ];
    }

    return $matrix;
}
