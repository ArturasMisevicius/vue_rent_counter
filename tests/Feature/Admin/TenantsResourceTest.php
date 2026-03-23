<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Actions\Admin\Tenants\UpdateTenantAction;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('shows organization-scoped tenant resource pages and assignment-aware tenant details', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.com',
        'locale' => 'lt',
        'status' => UserStatus::ACTIVE,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subDays(14),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-200001',
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
        'name' => 'Other Tenant',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 10,
    ]);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText('Tenants')
        ->assertSeeText($tenant->name)
        ->assertSeeText($tenant->email)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherTenant->name);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('Preferred Language')
        ->assertSeeText('Initial Status')
        ->assertSeeText('Assign Property');

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful()
        ->assertSeeText('Tenant Details')
        ->assertSeeText('Profile')
        ->assertSeeText('Audit Trail')
        ->assertSeeText('Reassign Property')
        ->assertSeeText('Current Property')
        ->assertSeeText('Invoice History')
        ->assertSeeText('INV-200001')
        ->assertSeeText('Taylor Tenant');

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.edit', $tenant))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText('Assign Property');

    actingAs($manager)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText($tenant->name);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $otherTenant))
        ->assertForbidden();

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.edit', $otherTenant))
        ->assertForbidden();

    actingAs($manager)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('Assign Property');

    actingAs($superadmin)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText($tenant->name)
        ->assertSeeText($otherTenant->name);
});

it('creates tenants through invitation reuse with optional property assignment and supports updates plus status toggles', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'B-21',
        'floor_area_sqm' => 48.5,
    ]);
    $nextProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'B-22',
        'floor_area_sqm' => 51.25,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    actingAs($admin);

    $tenant = app(CreateTenantAction::class)->handle($admin, [
        'name' => 'Pat Tenant',
        'email' => 'pat@example.com',
        'locale' => 'lt',
        'status' => UserStatus::INACTIVE,
        'property_id' => $property->id,
        'unit_area_sqm' => 48.5,
    ]);

    expect($tenant)
        ->name->toBe('Pat Tenant')
        ->email->toBe('pat@example.com')
        ->role->toBe(UserRole::TENANT)
        ->status->toBe(UserStatus::INACTIVE)
        ->locale->toBe('lt')
        ->organization_id->toBe($organization->id);

    expect($tenant->fresh()->currentProperty?->is($property))->toBeTrue();

    $invitation = OrganizationInvitation::query()
        ->where('organization_id', $organization->id)
        ->where('email', 'pat@example.com')
        ->whereNull('accepted_at')
        ->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation?->role)->toBe(UserRole::TENANT)
        ->and($invitation?->full_name)->toBe('Pat Tenant');

    Notification::assertSentOnDemand(
        OrganizationInvitationNotification::class,
        function (OrganizationInvitationNotification $notification, array $channels, object $notifiable) use ($invitation): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $invitation?->email
                && $notification->invitation->is($invitation);
        },
    );

    $updated = app(UpdateTenantAction::class)->handle($tenant->fresh(), [
        'name' => 'Pat Tenant Updated',
        'email' => 'pat.updated@example.com',
        'locale' => 'ru',
        'status' => UserStatus::ACTIVE,
        'property_id' => $nextProperty->id,
        'unit_area_sqm' => 51.25,
    ]);

    expect($updated)
        ->name->toBe('Pat Tenant Updated')
        ->email->toBe('pat.updated@example.com')
        ->locale->toBe('ru')
        ->status->toBe(UserStatus::ACTIVE)
        ->and($updated->fresh()->currentProperty?->is($nextProperty))->toBeTrue()
        ->and($updated->propertyAssignments()->count())->toBe(2);

    $deactivated = app(ToggleTenantStatusAction::class)->handle($updated->fresh());
    $reactivated = app(ToggleTenantStatusAction::class)->handle($deactivated->fresh());

    expect($deactivated->status)->toBe(UserStatus::INACTIVE)
        ->and($reactivated->status)->toBe(UserStatus::ACTIVE);
});

it('keeps the tenant create page reachable at the limit and prevents deleting tenants with invoice history', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 1,
    ]);

    $tenantAtLimit = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
    ]);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful();

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenantAtLimit, 'tenant')
        ->create();

    expect(fn () => app(DeleteTenantAction::class)->handle($tenantAtLimit))
        ->toThrow(ValidationException::class);

    expect(User::query()->whereKey($tenantAtLimit->id)->exists())->toBeTrue();

    $deletableTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    app(DeleteTenantAction::class)->handle($deletableTenant);

    expect(User::query()->whereKey($deletableTenant->id)->exists())->toBeFalse();
});

it('rejects creating tenants with disposable email domains', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    expect(fn () => app(CreateTenantAction::class)->handle($admin, [
        'name' => 'Disposable Tenant',
        'email' => 'tenant@10minutemail.com',
        'locale' => 'en',
        'status' => UserStatus::INACTIVE,
        'property_id' => null,
        'unit_area_sqm' => null,
    ]))->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'tenant@10minutemail.com')->exists())->toBeFalse()
        ->and(OrganizationInvitation::query()->where('email', 'tenant@10minutemail.com')->exists())->toBeFalse();

    Notification::assertNothingSent();
});
