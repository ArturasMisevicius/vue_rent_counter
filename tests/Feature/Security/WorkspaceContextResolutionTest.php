<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (Route::has('test.security.workspace-context')) {
        return;
    }

    Route::middleware(['web', 'auth', 'set.auth.locale', 'ensure.account.accessible'])
        ->get('/__test/security/workspace-context', function (WorkspaceResolver $workspaceResolver) {
            return response()->json($workspaceResolver->current()?->toArray());
        })
        ->name('test.security.workspace-context');

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('resolves a platform workspace for superadmins without an organization assignment', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'platform',
            'role' => UserRole::SUPERADMIN->value,
            'organization_id' => null,
            'property_id' => null,
            'user_id' => $superadmin->id,
        ]);
});

it('resolves the authenticated organization workspace for admin-like users', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'organization',
            'role' => UserRole::MANAGER->value,
            'organization_id' => $organization->id,
            'property_id' => null,
            'user_id' => $manager->id,
        ]);
});

it('falls back to the authenticated user when the current request has no bound request user', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    app()->instance('request', Request::create('/__test/security/workspace-context/fallback', 'GET'));

    expect(app(WorkspaceResolver::class)->current()?->toArray())->toMatchArray([
        'scope' => 'organization',
        'role' => UserRole::ADMIN->value,
        'organization_id' => $organization->id,
        'property_id' => null,
        'user_id' => $admin->id,
    ]);
});

it('fails closed when a non-onboarding workspace account has no organization assignment', function () {
    $manager = User::factory()->manager()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($manager)
        ->get(route('test.security.workspace-context'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('drops malformed tenant property assignments from the resolved workspace contract', function () {
    $tenantOrganization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $tenantOrganization->id,
    ]);

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $this->actingAs($tenant)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'tenant',
            'role' => UserRole::TENANT->value,
            'organization_id' => $tenantOrganization->id,
            'property_id' => null,
            'user_id' => $tenant->id,
        ]);
});

it('keeps tenant shell access even when tenant has no organization assignment', function () {
    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($tenant)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'tenant',
            'role' => UserRole::TENANT->value,
            'organization_id' => null,
            'property_id' => null,
            'user_id' => $tenant->id,
        ]);
});

it('includes the tenants current property when the assignment belongs to the same workspace', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $this->actingAs($tenant)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'tenant',
            'role' => UserRole::TENANT->value,
            'organization_id' => $organization->id,
            'property_id' => $property->id,
            'user_id' => $tenant->id,
        ]);
});

it('recovers tenant organization context from a valid current assignment when the user organization is missing', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subWeek(),
            'unassigned_at' => null,
        ]);

    $this->actingAs($tenant)
        ->get(route('test.security.workspace-context'))
        ->assertOk()
        ->assertJson([
            'scope' => 'tenant',
            'role' => UserRole::TENANT->value,
            'organization_id' => $organization->id,
            'property_id' => $property->id,
            'user_id' => $tenant->id,
        ]);

    expect($tenant->fresh()?->organization_id)->toBe($organization->id);
});
