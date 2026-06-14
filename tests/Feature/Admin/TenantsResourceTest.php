<?php

use App\Enums\BillingReadinessStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\CreateTenantAction;
use App\Filament\Actions\Admin\Tenants\CreateTenantWithAssignment;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Actions\Admin\Tenants\UpdateTenantAction;
use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\RelationManagers\AuditTrailRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Tenants\RelationManagers\ReadingsRelationManager;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Tenants\CheckTenantBillingReadiness;
use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use App\Services\SubscriptionChecker;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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
        'email_verified_at' => now()->subDays(10),
        'last_login_at' => now()->subDay(),
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

    Notification::fake();

    $managerMatrix = ManagerPermissionCatalog::defaultMatrix();
    $managerMatrix['tenants']['can_create'] = true;

    app(ManagerPermissionService::class)->saveMatrix($manager, $organization, $managerMatrix, $admin);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful()
        ->assertSeeText('Tenants')
        ->assertSeeText('Create Tenant')
        ->assertSeeText($tenant->name)
        ->assertSeeText($tenant->email)
        ->assertSeeText($tenant->phone)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherTenant->name);

    actingAs($admin)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('New Tenant')
        ->assertSeeText('Tenant Details')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Do not set a tenant password here.')
        ->assertSeeText('Property Assignment')
        ->assertSeeText('Billing Setup')
        ->assertSeeText('Phone Number')
        ->assertSeeText('Tenant Status')
        ->assertSeeText('Preferred Portal Language');

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
        ->assertSeeText('Organization')
        ->assertSeeText($organization->name)
        ->assertSeeText('Account Activity')
        ->assertSeeText('Email Verified')
        ->assertSeeText('Updated At')
        ->assertSeeText('Edit Tenant')
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
        ->assertSeeText($otherTenant->name)
        ->assertSeeText('Create Tenant');

    actingAs($superadmin)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful()
        ->assertSeeText('New Tenant')
        ->assertSeeText('Organization')
        ->assertSeeText('Tenant Details')
        ->assertSeeText('Property Assignment');
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
        'locale' => 'lt',
        'last_login_at' => now()->subDay(),
    ]);
    $suspendedTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Suspended Tenant',
        'status' => UserStatus::SUSPENDED,
        'locale' => 'en',
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
        ->assertTableColumnExists('locale', fn (TextColumn $column): bool => $column->getLabel() === 'Preferred Language')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('last_login_at', fn (TextColumn $column): bool => $column->getLabel() === 'Last Login')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Date Added')
        ->assertTableFilterExists('property_id')
        ->assertTableFilterExists('locale')
        ->assertTableFilterExists('status')
        ->assertCanSeeTableRecords([$tenantA])
        ->searchTable('Jordan Tenant')
        ->assertCanSeeTableRecords([$tenantA])
        ->assertCanNotSeeTableRecords([$tenantB])
        ->searchTable()
        ->assertCanNotSeeTableRecords([$tenantB]);

    Livewire::test(ListTenants::class)
        ->filterTable('status', UserStatus::SUSPENDED->value)
        ->assertCanSeeTableRecords([$suspendedTenant])
        ->assertCanNotSeeTableRecords([$tenantA])
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
        'move_in_date' => '2026-07-01',
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

it('creates tenants through the wizard action with assignment metadata, audit logs, and no admin-set password', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create([
            'name' => 'Wizard-12',
            'floor_area_sqm' => 44.25,
        ]);

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->fixedMonthly('39.00')
        ->create([
            'effective_from' => now()->subDay(),
            'effective_until' => null,
        ]);

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    actingAs($admin);

    $result = app(CreateTenantWithAssignment::class)->handle($admin, [
        'first_name' => 'Wizard',
        'last_name' => 'Tenant',
        'email' => 'wizard.tenant@example.com',
        'phone' => '+37060001010',
        'locale' => 'en',
        'portal_locale' => 'lt',
        'tenant_status' => TenantStatus::ACTIVE->value,
        'property_id' => $property->id,
        'unit_area_sqm' => 44.25,
        'move_in_date' => '2026-07-01',
        'assignment_status' => PropertyAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
        'occupants_count' => 2,
        'create_portal_access' => true,
        'send_invitation_now' => false,
        'password' => 'AdminPickedPassword123!',
    ]);

    $tenant = $result->tenant->fresh(['currentPropertyAssignment']);
    $assignment = $tenant->currentPropertyAssignment;

    expect($tenant->name)->toBe('Wizard Tenant')
        ->and($tenant->tenant_status)->toBe(TenantStatus::ACTIVE)
        ->and($tenant->locale)->toBe('lt')
        ->and(Hash::check('AdminPickedPassword123!', (string) $tenant->password))->toBeFalse()
        ->and($tenant->latestTenantInvitationRecord())->toBeNull()
        ->and($tenant->portalAccessStatus()->value)->toBe('not_invited')
        ->and($assignment)->not->toBeNull()
        ->and($assignment?->property_id)->toBe($property->id)
        ->and($assignment?->status)->toBe(PropertyAssignmentStatus::ACTIVE)
        ->and($assignment?->is_primary)->toBeTrue()
        ->and($assignment?->occupants_count)->toBe(2)
        ->and($assignment?->assigned_at?->toDateString())->toBe('2026-07-01')
        ->and($assignment?->created_by_user_id)->toBe($admin->id)
        ->and($result->billingReadiness->status)->toBe(BillingReadinessStatus::READY);

    $mutations = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->get()
        ->map(fn (AuditLog $log): mixed => data_get($log->metadata, 'context.mutation'))
        ->all();

    expect($mutations)->toContain('tenant.created')
        ->and($mutations)->toContain('tenant_property_assignment.created');

    Notification::assertNothingSent();
});

it('requires move-in dates and prevents duplicate active primary property assignments', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    actingAs($admin);

    expect(fn () => app(CreateTenantWithAssignment::class)->handle($admin, [
        'name' => 'No Move In',
        'email' => 'no.movein@example.com',
        'locale' => 'en',
        'property_id' => $property->id,
        'assignment_status' => PropertyAssignmentStatus::ACTIVE->value,
    ]))->toThrow(ValidationException::class);

    $existingTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($existingTenant, 'tenant')
        ->create([
            'status' => PropertyAssignmentStatus::ACTIVE,
            'is_primary' => true,
            'assigned_at' => now()->subDay(),
        ]);

    expect(fn () => app(CreateTenantWithAssignment::class)->handle($admin, [
        'name' => 'Second Tenant',
        'email' => 'second.tenant@example.com',
        'locale' => 'en',
        'property_id' => $property->id,
        'move_in_date' => '2026-07-01',
        'assignment_status' => PropertyAssignmentStatus::ACTIVE->value,
    ]))->toThrow(ValidationException::class);
});

it('warns on duplicate tenant emails and phones in the same organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'duplicate.email@example.com',
    ]);
    User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'phone' => '+37060009999',
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    actingAs($admin);

    try {
        app(CreateTenantWithAssignment::class)->handle($admin, [
            'name' => 'Duplicate Email',
            'email' => 'duplicate.email@example.com',
            'locale' => 'en',
            'property_id' => null,
        ]);

        expect('duplicate email validation')->toBe('thrown');
    } catch (ValidationException $exception) {
        expect($exception->errors()['email'][0] ?? null)
            ->toBe(__('admin.tenants.messages.duplicate_email_warning'));
    }

    expect(fn () => app(CreateTenantWithAssignment::class)->handle($admin, [
        'name' => 'Duplicate Phone',
        'email' => 'duplicate.phone@example.com',
        'phone' => '+37060009999',
        'locale' => 'en',
        'property_id' => null,
    ]))->toThrow(ValidationException::class);
});

it('checks tenant billing readiness for missing assignments, tariffs, and opening readings', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $readiness = app(CheckTenantBillingReadiness::class)->handle($tenant);

    expect($readiness->status)->toBe(BillingReadinessStatus::NOT_CONFIGURED);

    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->fixedMonthly('10.00')
        ->create([
            'fixed_amount' => null,
            'tariff_id' => null,
            'effective_from' => now()->subDay(),
            'effective_until' => null,
        ]);

    $blocked = app(CheckTenantBillingReadiness::class)->handle($tenant->fresh(['currentPropertyAssignment.property']));

    expect($blocked->status)->toBe(BillingReadinessStatus::BLOCKED)
        ->and($blocked->blockingErrors)->not->toBeEmpty();

    $meterProperty = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();
    $portalTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
        'portal_access_enabled' => true,
    ]);
    PropertyAssignment::factory()
        ->for($organization)
        ->for($meterProperty)
        ->for($portalTenant, 'tenant')
        ->create();
    Meter::factory()
        ->for($organization)
        ->for($meterProperty)
        ->create([
            'identifier' => 'OPEN-MISSING',
        ]);
    ServiceConfiguration::factory()
        ->for($organization)
        ->for($meterProperty)
        ->create([
            'effective_from' => now()->subDay(),
            'effective_until' => null,
        ]);

    $warning = app(CheckTenantBillingReadiness::class)->handle($portalTenant->fresh(['currentPropertyAssignment.property']));

    expect($warning->status)->toBe(BillingReadinessStatus::WARNING)
        ->and(implode(' ', $warning->warnings))->toContain('Opening reading');
});

it('lets admins attach and replace a lease agreement for a tenant', function () {
    Storage::fake(TenantLeaseAgreement::DISK);
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    Subscription::factory()->for($workspace['organization'])->active()->create([
        'tenant_limit_snapshot' => 5,
    ]);

    actingAs($workspace['admin']);

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->assertFormFieldExists(TenantLeaseAgreement::FIELD, fn (FormFileUpload $field): bool => $field->getLabel() === 'Lease Agreement'
            && $field->getAcceptedFileTypes() === TenantLeaseAgreement::acceptedFileTypes()
            && $field->getMaxSize() === TenantLeaseAgreement::MAX_SIZE_KB
            && $field->isOpenable()
            && $field->isDownloadable())
        ->fillForm([
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'locale' => $tenant->locale,
            'property_id' => null,
            'unit_area_sqm' => null,
            TenantLeaseAgreement::FIELD => UploadedFile::fake()->create('lease.pdf', 64, 'application/pdf'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $firstAttachment = $tenant->fresh()->leaseAgreement;

    expect($firstAttachment)->not->toBeNull()
        ->and($firstAttachment?->document_type)->toBe(TenantLeaseAgreement::DOCUMENT_TYPE)
        ->and($firstAttachment?->original_filename)->toBe('lease.pdf')
        ->and($firstAttachment?->organization_id)->toBe($workspace['organization']->id)
        ->and($firstAttachment?->uploaded_by_user_id)->toBe($workspace['admin']->id);

    Storage::disk(TenantLeaseAgreement::DISK)->assertExists((string) $firstAttachment?->path);

    $firstPath = (string) $firstAttachment?->path;

    Livewire::test(EditTenant::class, ['record' => $tenant->getRouteKey()])
        ->fillForm([
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'locale' => $tenant->locale,
            'property_id' => null,
            'unit_area_sqm' => null,
            TenantLeaseAgreement::FIELD => UploadedFile::fake()->create('lease-updated.docx', 48, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $replacement = $tenant->fresh()->leaseAgreement;

    expect($replacement)->not->toBeNull()
        ->and($replacement?->original_filename)->toBe('lease-updated.docx')
        ->and($tenant->fresh()->leaseAgreements()->count())->toBe(1);

    if ($replacement?->path !== $firstPath) {
        Storage::disk(TenantLeaseAgreement::DISK)->assertMissing($firstPath);
    }

    Storage::disk(TenantLeaseAgreement::DISK)->assertExists((string) $replacement?->path);

    Livewire::test(ListTenants::class)
        ->assertTableColumnExists('leaseAgreement.original_filename', fn (TextColumn $column): bool => $column->getLabel() === 'Lease Agreement');
});

it('serves tenant lease agreements only inside the tenant access boundary', function () {
    Storage::fake(TenantLeaseAgreement::DISK);

    $workspace = createOrgWithAdmin();
    $otherWorkspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];
    $otherTenant = createTenantInOrg($otherWorkspace['admin'])['tenant'];

    $path = TenantLeaseAgreement::DIRECTORY.'/lease.pdf';

    Storage::disk(TenantLeaseAgreement::DISK)->put($path, 'lease-pdf');

    $attachment = Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($tenant, 'attachable')
        ->create([
            'document_type' => TenantLeaseAgreement::DOCUMENT_TYPE,
            'filename' => 'lease.pdf',
            'original_filename' => 'lease.pdf',
            'mime_type' => 'application/pdf',
            'disk' => TenantLeaseAgreement::DISK,
            'path' => $path,
        ]);

    actingAs($workspace['admin']);

    get(route('tenant.attachments.show', $attachment))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    actingAs($tenant);

    get(route('tenant.attachments.show', $attachment))
        ->assertSuccessful();

    actingAs($otherWorkspace['admin']);

    get(route('tenant.attachments.show', $attachment))
        ->assertForbidden();

    actingAs($otherTenant);

    get(route('tenant.attachments.show', $attachment))
        ->assertForbidden();
});

it('allows superadmins to create tenants by selecting an organization', function () {
    Notification::fake();

    $organization = Organization::factory()->create([
        'name' => 'Harbor Estates',
    ]);
    $organizationAdmin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'harbor.admin@example.com',
    ]);
    $superadmin = User::factory()->superadmin()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'H-21',
        'floor_area_sqm' => 63.5,
    ]);

    actingAs($superadmin);

    Livewire::test(ListTenants::class)
        ->assertActionVisible('create');

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'organization_id' => $organization->id,
            'name' => 'Global Tenant',
            'email' => 'global.tenant@example.com',
            'phone' => '+37060000009',
            'locale' => 'en',
            'property_id' => $property->id,
            'unit_area_sqm' => 63.5,
            'move_in_date' => '2026-07-01',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = User::query()
        ->where('email', 'global.tenant@example.com')
        ->firstOrFail();

    expect($tenant->organization_id)->toBe($organization->id)
        ->and($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->currentProperty?->is($property))->toBeTrue();

    $invitation = OrganizationInvitation::query()
        ->where('organization_id', $organization->id)
        ->where('email', 'global.tenant@example.com')
        ->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation?->inviter_user_id)->toBe($organizationAdmin->id);
});

it('resets property assignment state when superadmins change the selected organization', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $building = Building::factory()->for($organizationA)->create();
    $property = Property::factory()->for($organizationA)->for($building)->create([
        'floor_area_sqm' => 71.25,
    ]);

    actingAs($superadmin);

    Livewire::test(CreateTenant::class)
        ->set('data.organization_id', $organizationA->id)
        ->set('data.property_id', $property->id)
        ->assertSet('data.unit_area_sqm', 71.25)
        ->set('data.organization_id', $organizationB->id)
        ->assertSet('data.property_id', null)
        ->assertSet('data.unit_area_sqm', null);
});

it('rejects superadmin tenant creation with a property from another organization', function () {
    Notification::fake();

    $organizationA = Organization::factory()->create();
    User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $organizationB = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();
    $building = Building::factory()->for($organizationB)->create();
    $propertyFromAnotherOrganization = Property::factory()->for($organizationB)->for($building)->create();

    actingAs($superadmin);

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'organization_id' => $organizationA->id,
            'name' => 'Cross Org Tenant',
            'email' => 'cross.org.tenant@example.com',
            'phone' => '+37060000010',
            'locale' => 'en',
            'property_id' => $propertyFromAnotherOrganization->id,
            'unit_area_sqm' => 48,
        ])
        ->call('create')
        ->assertHasFormErrors(['property_id']);

    expect(User::query()->where('email', 'cross.org.tenant@example.com')->exists())->toBeFalse();
});

it('validates tenant creation against the explicit actor passed to the action', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    app(CreateTenantAction::class)->handle($superadmin, [
        'name' => 'Explicit Actor Tenant',
        'email' => 'explicit.actor@example.com',
        'phone' => '+37060000011',
        'locale' => 'en',
        'property_id' => null,
        'unit_area_sqm' => null,
    ], $organization);

    expect(User::query()
        ->where('organization_id', $organization->id)
        ->where('email', 'explicit.actor@example.com')
        ->exists())->toBeTrue();
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

it('keeps tenant relation tab badges deferred with relation counts', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $meter = Meter::factory()->for($organization)->for($property)->create();
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create();
    Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create();
    OrganizationActivityLog::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $tenant->id,
        'resource_type' => User::class,
        'resource_id' => $tenant->id,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    actingAs($admin);

    $component = Livewire::test(ViewTenant::class, ['record' => $tenant->getRouteKey()]);
    $page = $component->invade();
    $record = $page->getRecord();
    $tabs = collect($page->getDeferredRelationManagerTabs(
        TenantResource::getRelations(),
        $page->hasCombinedRelationManagerTabsWithContent(),
        ['ownerRecord' => $record, 'pageClass' => ViewTenant::class],
        $record,
    ));

    $tabs->except([''])->each(function (Tab $tab): void {
        expect($tab->isBadgeDeferred())->toBeTrue();
    });

    expect(MetersRelationManager::getBadge($record, ViewTenant::class))->toBe('1')
        ->and(ReadingsRelationManager::getBadge($record, ViewTenant::class))->toBe('1')
        ->and(InvoicesRelationManager::getBadge($record, ViewTenant::class))->toBe('1')
        ->and(AuditTrailRelationManager::getBadge($record, ViewTenant::class))->toBe((string) $record->resourceActivityLogs()->count());
});
