<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Organization;
use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('only allows superadmins to reach users control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
});

it('lists platform users with filters and row actions', function () {
    $superadmin = User::factory()->superadmin()->create();
    $atlas = Organization::factory()->create(['name' => 'Atlas Plaza']);
    $birch = Organization::factory()->create(['name' => 'Birch Court']);

    $atlasAdmin = User::factory()->admin()->create([
        'name' => 'Atlas Admin',
        'organization_id' => $atlas->id,
        'last_login_at' => now()->subHour(),
    ]);
    $birchManager = User::factory()->manager()->suspended()->create([
        'name' => 'Birch Manager',
        'organization_id' => $birch->id,
        'last_login_at' => now()->subDays(45),
    ]);
    $atlasTenant = User::factory()->tenant()->create([
        'name' => 'Atlas Tenant',
        'organization_id' => $atlas->id,
        'last_login_at' => null,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$superadmin, $atlasAdmin, $birchManager, $atlasTenant])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('role')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('organization.name')
        ->assertTableColumnExists('last_login_at')
        ->assertTableFilterExists('role')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('organization')
        ->assertTableFilterExists('last_login')
        ->assertTableActionVisible('view', $atlasAdmin)
        ->assertTableActionVisible('edit', $atlasAdmin)
        ->assertTableActionVisible('impersonate', $atlasAdmin)
        ->assertTableActionHidden('impersonate', $superadmin);

    Livewire::test(ListUsers::class)
        ->filterTable('role', UserRole::MANAGER)
        ->assertCanSeeTableRecords([$birchManager])
        ->assertCanNotSeeTableRecords([$atlasAdmin, $atlasTenant]);

    Livewire::test(ListUsers::class)
        ->filterTable('status', UserStatus::SUSPENDED)
        ->assertCanSeeTableRecords([$birchManager])
        ->assertCanNotSeeTableRecords([$atlasAdmin, $atlasTenant]);

    Livewire::test(ListUsers::class)
        ->filterTable('organization', $atlas)
        ->assertCanSeeTableRecords([$atlasAdmin, $atlasTenant])
        ->assertCanNotSeeTableRecords([$birchManager]);

    Livewire::test(ListUsers::class)
        ->filterTable('last_login', true)
        ->assertCanSeeTableRecords([$atlasAdmin])
        ->assertCanNotSeeTableRecords([$birchManager, $atlasTenant]);
});

it('creates and edits users with the minimal platform account fields', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Platform Manager',
            'email' => 'platform.manager@example.test',
            'role' => UserRole::MANAGER->value,
            'organization_id' => $organization->id,
            'status' => UserStatus::ACTIVE->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()
        ->where('email', 'platform.manager@example.test')
        ->firstOrFail();

    expect($user->role)->toBe(UserRole::MANAGER)
        ->and($user->organization_id)->toBe($organization->id)
        ->and($user->status)->toBe(UserStatus::ACTIVE);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'name' => 'Platform Manager Updated',
            'email' => 'platform.manager.updated@example.test',
            'role' => UserRole::ADMIN->value,
            'organization_id' => $organization->id,
            'status' => UserStatus::SUSPENDED->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('Platform Manager Updated')
        ->and($user->email)->toBe('platform.manager.updated@example.test')
        ->and($user->role)->toBe(UserRole::ADMIN)
        ->and($user->status)->toBe(UserStatus::SUSPENDED);
});

it('disables deleting users when dependent organization ownership exists', function () {
    $superadmin = User::factory()->superadmin()->create();
    $owner = User::factory()->admin()->create();
    $safeUser = User::factory()->manager()->create();

    Organization::factory()->create([
        'owner_user_id' => $owner->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->assertTableActionDisabled('delete', $owner)
        ->assertTableActionEnabled('delete', $safeUser);
});

it('starts impersonation from a user row action', function () {
    $superadmin = User::factory()->superadmin()->create();
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->callTableAction('impersonate', $tenant);

    $this->assertAuthenticatedAs($tenant);
    expect(session(ImpersonationManager::IMPERSONATOR_ID))->toBe($superadmin->id)
        ->and(session(ImpersonationManager::IMPERSONATOR_EMAIL))->toBe($superadmin->email)
        ->and(session(ImpersonationManager::IMPERSONATOR_NAME))->toBe($superadmin->name);
});
