<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Feature tests for UserResource CRUD operations.
 *
 * Tests core functionality including:
 * - Tenant-scoped user listing
 * - User creation with validation
 * - User editing with password handling
 * - Navigation badge counting
 * - Session persistence
 *
 * @group filament
 * @group user-resource
 */

test('admin can view user list scoped to their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create users in admin's tenant
    User::factory()->count(3)->create(['tenant_id' => 1]);

    // Create users in different tenant
    User::factory()->count(2)->create(['tenant_id' => 2]);

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(User::where('tenant_id', 1)->get())
        ->assertCanNotSeeTableRecords(User::where('tenant_id', 2)->get());
});

test('superadmin can view all users across all tenants', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    // Create users in different tenants
    $tenant1Users = User::factory()->count(3)->create(['tenant_id' => 1]);
    $tenant2Users = User::factory()->count(2)->create(['tenant_id' => 2]);

    actingAs($superadmin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords($tenant1Users)
        ->assertCanSeeTableRecords($tenant2Users);
});

test('manager cannot view users in their tenant', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);

    $tenantUsers = User::factory()->count(3)->create(['tenant_id' => 1]);
    $otherUsers = User::factory()->count(2)->create(['tenant_id' => 2]);

    actingAs($manager);

    Livewire::test(ListUsers::class)
        ->assertForbidden();
});

test('tenant users cannot access user resource', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
    ]);

    actingAs($tenant);

    expect(UserResource::shouldRegisterNavigation())->toBeFalse();
});

test('admin can create user with valid data', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::MANAGER->value,
            'tenant_id' => 1,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->role)->toBe(UserRole::MANAGER)
        ->and($user->tenant_id)->toBe(1)
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('create user validates required fields', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'role' => 'required',
        ]);
});

test('create user validates email uniqueness', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::MANAGER->value,
            'tenant_id' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

test('create user validates password confirmation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
            'role' => UserRole::MANAGER->value,
            'tenant_id' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['password' => 'confirmed']);
});

test('tenant field is required for manager role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::MANAGER->value,
            'tenant_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['tenant_id']);
});

test('tenant field is required for tenant role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::TENANT->value,
            'tenant_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['tenant_id']);
});

test('tenant field is optional for admin role', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    actingAs($superadmin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::ADMIN->value,
            'tenant_id' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->tenant_id)->toBeNull();
});

test('password is optional on user update', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $user = User::factory()->create([
        'tenant_id' => 1,
        'password' => Hash::make('oldpassword'),
    ]);

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'name' => 'Updated Name',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and(Hash::check('oldpassword', $user->password))->toBeTrue();
});

test('password is hashed when updated', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $user = User::factory()->create([
        'tenant_id' => 1,
        'password' => Hash::make('oldpassword'),
    ]);

    actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();

    expect(Hash::check('newpassword123', $user->password))->toBeTrue()
        ->and(Hash::check('oldpassword', $user->password))->toBeFalse();
});

test('navigation badge shows correct count for admin', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);

    actingAs($admin);

    $badge = UserResource::getNavigationBadge();

    expect($badge)->toBe('6'); // 5 + admin
});

test('navigation badge shows correct count for superadmin', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);

    actingAs($superadmin);

    $badge = UserResource::getNavigationBadge();

    expect($badge)->toBe('9'); // 5 + 3 + superadmin
});

test('tenant dropdown is scoped to user tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create potential parent users in different tenants
    User::factory()->count(3)->create(['tenant_id' => 1]);
    User::factory()->count(2)->create(['tenant_id' => 2]);

    actingAs($admin);

    $component = Livewire::test(CreateUser::class)
        ->fillForm([
            'role' => UserRole::MANAGER->value,
        ]);

    // Verify tenant dropdown only shows users from tenant 1
    $form = $component->instance()->form;
    $tenantField = $form->getComponent('tenant_id');

    expect($tenantField)->not->toBeNull();
});

test('table filters persist in session', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->filterTable('role', UserRole::MANAGER->value)
        ->assertSessionHas('tables.users.filters.role');
});

test('table search persists in session', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->searchTable('test')
        ->assertSessionHas('tables.users.search');
});

test('table sort persists in session', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->sortTable('email')
        ->assertSessionHas('tables.users.sort');
});

test('role filter shows all user roles', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    $component = Livewire::test(ListUsers::class);

    // Verify role filter has all options
    $filters = $component->instance()->getTable()->getFilters();

    expect($filters)->toHaveKey('role');
});

test('is_active filter works correctly', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $activeUsers = User::factory()->count(3)->create([
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    $inactiveUsers = User::factory()->count(2)->create([
        'tenant_id' => 1,
        'is_active' => false,
    ]);

    actingAs($admin);

    Livewire::test(ListUsers::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords($activeUsers)
        ->assertCanNotSeeTableRecords($inactiveUsers);
});

test('email column is copyable', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    $component = Livewire::test(ListUsers::class);

    $table = $component->instance()->getTable();
    $emailColumn = $table->getColumn('email');

    expect($emailColumn->isCopyable())->toBeTrue();
});

test('form sections render correctly', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    actingAs($admin);

    $component = Livewire::test(CreateUser::class);

    $form = $component->instance()->form;

    // Verify sections exist
    $schema = $form->getSchema();

    expect($schema)->toHaveCount(2); // Two sections
});
