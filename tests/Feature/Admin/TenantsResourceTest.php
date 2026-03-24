<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Actions\Admin\Tenants\UpdateTenantAction;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\RelationManagers\AuditTrailRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\ReadingsRelationManager;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use App\Services\SubscriptionChecker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renders tenant pages with the admin contract and organization-scoped data', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
        'address_line_1' => 'North Street 10',
        'city' => 'Vilnius',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
        'floor' => 4,
        'floor_area_sqm' => 48.5,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.com',
        'phone' => '+37060000001',
        'locale' => 'lt',
        'status' => UserStatus::ACTIVE,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'unit_area_sqm' => 48.5,
            'assigned_at' => now()->subDays(14),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-200001',
            'status' => InvoiceStatus::PAID,
            'amount_paid' => 150.00,
            'paid_amount' => 150.00,
            'paid_at' => now(),
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
        ->assertSeeText('New Tenant')
        ->assertSeeText($tenant->name)
        ->assertSeeText($tenant->email)
        ->assertSeeText($tenant->phone)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherTenant->name);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('New Tenant')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Property Assignment')
        ->assertSeeText('Phone Number')
        ->assertDontSeeText('Initial Status');

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful()
        ->assertSeeText('Taylor Tenant')
        ->assertSeeText('taylor@example.com')
        ->assertSeeText('Profile')
        ->assertSeeText('Meters')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText('Audit Trail')
        ->assertSeeText('Tenant Summary')
        ->assertSeeText('Total Paid')
        ->assertSeeText('Reassign Property')
        ->assertSeeText('Deactivate')
        ->assertSeeText('Delete')
        ->assertSeeText('North Hall')
        ->assertSeeText('48.5 m²');

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.edit', $tenant))
        ->assertSuccessful()
        ->assertSeeText('Edit Tenant: Taylor Tenant')
        ->assertSeeText('Save Changes')
        ->assertSeeText('Phone Number')
        ->assertDontSeeText('Initial Status');

    actingAs($manager)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText($tenant->name);

    actingAs($manager)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('Property Assignment');

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $otherTenant))
        ->assertForbidden();

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.edit', $otherTenant))
        ->assertForbidden();

    actingAs($superadmin)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText($tenant->name)
        ->assertSeeText($otherTenant->name);
});

it('exposes the tenants list and relation manager contracts', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
    ]);
    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'Aurora Block',
    ]);

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
        'floor_area_sqm' => 48.25,
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Alicia Admin',
        'email' => 'alicia@example.com',
    ]);
    $superadmin = User::factory()->superadmin()->create();

    Subscription::factory()->for($organizationA)->active()->create([
        'tenant_limit_snapshot' => 10,
    ]);

    $tenantA = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Jordan Tenant',
        'phone' => '+37060000111',
        'status' => UserStatus::ACTIVE,
    ]);
    $tenantB = User::factory()->tenant()->create([
        'organization_id' => $organizationB->id,
        'name' => 'Morgan Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($organizationA)
        ->for($propertyA)
        ->for($tenantA, 'tenant')
        ->create([
            'unit_area_sqm' => 48.25,
            'assigned_at' => now()->subDays(10),
        ]);

    $meter = Meter::factory()->for($organizationA)->for($propertyA)->create([
        'identifier' => 'MTR-3001',
    ]);

    MeterReading::factory()->for($organizationA)->for($propertyA)->for($meter)->create([
        'submitted_by_user_id' => $admin->id,
        'reading_value' => 120.5,
        'reading_date' => now()->subDays(2)->toDateString(),
    ]);
    $latestReading = MeterReading::factory()->for($organizationA)->for($propertyA)->for($meter)->create([
        'submitted_by_user_id' => $admin->id,
        'reading_value' => 146.75,
        'reading_date' => now()->toDateString(),
    ]);

    $invoice = Invoice::factory()
        ->for($organizationA)
        ->for($propertyA)
        ->for($tenantA, 'tenant')
        ->create([
            'invoice_number' => 'INV-2026-0042',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
        ]);

    OrganizationActivityLog::factory()->create([
        'organization_id' => $organizationA->id,
        'user_id' => $admin->id,
        'action' => 'tenant.updated',
        'resource_type' => User::class,
        'resource_id' => $tenantA->id,
        'metadata' => [
            'before' => [
                'status' => 'inactive',
            ],
            'after' => [
                'status' => 'active',
            ],
        ],
    ]);

    actingAs($admin);

    expect(app(OrganizationContext::class)->currentOrganizationId())->toBe($organizationA->id)
        ->and(app(SubscriptionChecker::class)->accessState($admin)->blocksCreation('tenants'))->toBeFalse()
        ->and(TenantResource::canCreate())->toBeTrue()
        ->and(TenantResource::shouldShowBlockedCreateAction('tenants'))->toBeFalse();

    Livewire::test(ListTenants::class)
        ->assertActionVisible('create')
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Full Name')
        ->assertTableColumnExists('currentPropertyAssignment.property.name', fn (TextColumn $column): bool => $column->getLabel() === 'Property')
        ->assertTableColumnExists('unit_area', fn (TextColumn $column): bool => $column->getLabel() === 'Unit Area')
        ->assertTableColumnExists('phone', fn (TextColumn $column): bool => $column->getLabel() === 'Phone')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Date Added')
        ->assertTableFilterExists('property_id')
        ->assertTableFilterExists('status')
        ->assertCanSeeTableRecords([$tenantA])
        ->searchTable('Jordan Tenant')
        ->assertCanSeeTableRecords([$tenantA])
        ->assertCanNotSeeTableRecords([$tenantB])
        ->searchTable()
        ->assertCanNotSeeTableRecords([$tenantB]);

    actingAs($superadmin);

    Livewire::test(ListTenants::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$tenantA, $tenantB])
        ->filterTable('organization', (string) $organizationA->id)
        ->assertCanSeeTableRecords([$tenantA])
        ->assertCanNotSeeTableRecords([$tenantB]);

    actingAs($admin);

    Livewire::test(ViewTenant::class, ['record' => $tenantA->getRouteKey()])
        ->assertActionExists('edit')
        ->assertActionExists('assignProperty')
        ->assertActionExists('toggleStatus')
        ->assertActionExists('delete');

    Livewire::test(MetersRelationManager::class, [
        'ownerRecord' => $tenantA,
        'pageClass' => ViewTenant::class,
    ])
        ->assertTableColumnExists('identifier', fn (TextColumn $column): bool => $column->getLabel() === 'Serial Number')
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Meter Type')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('latestReading.reading_date', fn (TextColumn $column): bool => $column->getLabel() === 'Last Reading Date')
        ->assertTableColumnExists('latestReading.reading_value', fn (TextColumn $column): bool => $column->getLabel() === 'Last Value')
        ->assertTableActionExists('view', record: $meter);

    Livewire::test(ReadingsRelationManager::class, [
        'ownerRecord' => $tenantA,
        'pageClass' => ViewTenant::class,
    ])
        ->assertTableColumnExists('meter.identifier', fn (TextColumn $column): bool => $column->getLabel() === 'Meter')
        ->assertTableColumnExists('reading_date', fn (TextColumn $column): bool => $column->getLabel() === 'Reading Date')
        ->assertTableColumnExists('reading_value', fn (TextColumn $column): bool => $column->getLabel() === 'Value')
        ->assertTableColumnExists('consumption_since_previous', fn (TextColumn $column): bool => $column->getLabel() === 'Consumption')
        ->assertTableColumnExists('validation_status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('submittedBy.name', fn (TextColumn $column): bool => $column->getLabel() === 'Submitted By')
        ->assertTableColumnExists('submission_method', fn (TextColumn $column): bool => $column->getLabel() === 'Submission Method')
        ->assertTableActionExists('view', record: $latestReading);

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $tenantA,
        'pageClass' => ViewTenant::class,
    ])
        ->assertTableColumnExists('invoice_number', fn (TextColumn $column): bool => $column->getLabel() === 'Invoice Number')
        ->assertTableColumnExists('billing_period', fn (TextColumn $column): bool => $column->getLabel() === 'Billing Period')
        ->assertTableColumnExists('total_amount', fn (TextColumn $column): bool => $column->getLabel() === 'Total Amount')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Issued Date')
        ->assertTableColumnExists('paid_at', fn (TextColumn $column): bool => $column->getLabel() === 'Paid Date')
        ->assertTableActionExists('view', record: $invoice)
        ->assertTableActionExists('downloadPdf', record: $invoice)
        ->assertTableActionExists('sendEmail', record: $invoice);

    Livewire::test(AuditTrailRelationManager::class, [
        'ownerRecord' => $tenantA,
        'pageClass' => ViewTenant::class,
    ])
        ->assertTableColumnExists('action', fn (TextColumn $column): bool => $column->getLabel() === 'Action')
        ->assertTableColumnExists('user.name', fn (TextColumn $column): bool => $column->getLabel() === 'Performed By')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Date and Time')
        ->assertTableActionDoesNotExist('view')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete');
});

it('creates invited tenants with phone and assignment data, updates them, and toggles their status', function () {
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
        'phone' => '+37060000002',
        'locale' => 'lt',
        'property_id' => $property->id,
        'unit_area_sqm' => 48.5,
    ]);

    expect($tenant)
        ->name->toBe('Pat Tenant')
        ->email->toBe('pat@example.com')
        ->phone->toBe('+37060000002')
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
        'phone' => '+37060000003',
        'locale' => 'ru',
        'property_id' => $nextProperty->id,
        'unit_area_sqm' => 51.25,
    ]);

    expect($updated)
        ->name->toBe('Pat Tenant Updated')
        ->email->toBe('pat.updated@example.com')
        ->phone->toBe('+37060000003')
        ->locale->toBe('ru')
        ->status->toBe(UserStatus::INACTIVE)
        ->and($updated->fresh()->currentProperty?->is($nextProperty))->toBeTrue()
        ->and($updated->propertyAssignments()->count())->toBe(2);

    $activated = app(ToggleTenantStatusAction::class)->handle($updated->fresh());
    $deactivated = app(ToggleTenantStatusAction::class)->handle($activated->fresh());

    expect($activated->status)->toBe(UserStatus::ACTIVE)
        ->and($deactivated->status)->toBe(UserStatus::INACTIVE);
});

it('prevents deleting tenants with invoice history and removes tenants without invoice history', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 2,
    ]);

    $tenantWithInvoice = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
    ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenantWithInvoice, 'tenant')
        ->create();

    expect(fn () => app(DeleteTenantAction::class)->handle($tenantWithInvoice))
        ->toThrow(ValidationException::class);

    expect(User::query()->whereKey($tenantWithInvoice->id)->exists())->toBeTrue();

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
        'phone' => '+37060000004',
        'locale' => 'en',
        'property_id' => null,
        'unit_area_sqm' => null,
    ]))->toThrow(ValidationException::class);

    expect(User::query()->where('email', 'tenant@10minutemail.com')->exists())->toBeFalse()
        ->and(OrganizationInvitation::query()->where('email', 'tenant@10minutemail.com')->exists())->toBeFalse();

    Notification::assertNothingSent();
});
