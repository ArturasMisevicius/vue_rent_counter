<?php

use App\Enums\AuditLogAction;
use App\Enums\RentalContractStatus;
use App\Filament\Actions\Admin\RentalContracts\CreateRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\ExpireRentalContractsAction;
use App\Filament\Actions\Admin\RentalContracts\RenewRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\SendContractExpiryReminderAction;
use App\Filament\Actions\Admin\RentalContracts\TerminateRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\UploadRentalContractFileAction;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\RentalContracts\RentalContractFile;
use App\Livewire\Tenant\RentalContracts;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\RentalContract;
use App\Models\User;
use App\Notifications\RentalContracts\RentalContractExpiryReminderNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('lets an admin create a rental contract in their own organization', function () {
    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);

    $contract = createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-OWN-001',
    ]);

    expect($contract)
        ->organization_id->toBe($workspace['organization']->id)
        ->tenant_id->toBe($assignment['tenant']->id)
        ->property_id->toBe($assignment['property']->id)
        ->status->toBe(RentalContractStatus::ACTIVE)
        ->created_by_user_id->toBe($workspace['admin']->id);
});

it('blocks admins from creating rental contracts in another organization', function () {
    $workspace = createOrgWithAdmin();
    $otherWorkspace = createOrgWithAdmin();
    $otherAssignment = createTenantInOrg($otherWorkspace['admin']);

    expect(fn () => createRentalContract($workspace['admin'], [
        'organization_id' => $otherWorkspace['organization']->id,
        'tenant_id' => $otherAssignment['tenant']->id,
        'property_id' => $otherAssignment['property']->id,
        'property_assignment_id' => $otherAssignment['assignment']->id,
        'contract_number' => 'RC-OTHER-001',
    ]))->toThrow(ValidationException::class);
});

it('requires manager rental contract permissions for write access', function () {
    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(fn () => createRentalContract($manager, [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-MANAGER-DENIED',
    ]))->toThrow(AuthorizationException::class);

    app(ManagerPermissionService::class)->saveMatrix(
        $manager,
        $workspace['organization'],
        rentalContractPermissionMatrix(['create' => true]),
        $workspace['admin'],
    );

    $contract = createRentalContract($manager->fresh(), [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-MANAGER-ALLOWED',
    ]);

    expect($contract->exists)->toBeTrue();
});

it('shows tenants only their own visible rental contracts', function () {
    $workspace = createOrgWithAdmin();
    $tenantA = createTenantInOrg($workspace['admin']);
    $tenantB = createTenantInOrg($workspace['admin']);

    $visible = RentalContract::factory()
        ->for($workspace['organization'])
        ->for($tenantA['tenant'], 'tenant')
        ->for($tenantA['property'])
        ->for($tenantA['assignment'], 'propertyAssignment')
        ->create([
            'contract_number' => 'RC-VISIBLE',
            'tenant_visible' => true,
        ]);

    RentalContract::factory()
        ->for($workspace['organization'])
        ->for($tenantA['tenant'], 'tenant')
        ->for($tenantA['property'])
        ->for($tenantA['assignment'], 'propertyAssignment')
        ->hiddenFromTenant()
        ->create([
            'contract_number' => 'RC-HIDDEN',
            'status' => RentalContractStatus::DRAFT,
        ]);

    RentalContract::factory()
        ->for($workspace['organization'])
        ->for($tenantB['tenant'], 'tenant')
        ->for($tenantB['property'])
        ->for($tenantB['assignment'], 'propertyAssignment')
        ->create([
            'contract_number' => 'RC-OTHER-TENANT',
            'tenant_visible' => true,
        ]);

    Livewire::actingAs($tenantA['tenant'])
        ->test(RentalContracts::class)
        ->assertSeeText($visible->contract_number)
        ->assertDontSeeText('RC-HIDDEN')
        ->assertDontSeeText('RC-OTHER-TENANT');
});

it('protects rental contract file downloads across tenants and organizations', function () {
    Storage::fake(RentalContractFile::DISK);

    $workspace = createOrgWithAdmin();
    $otherWorkspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);
    $otherTenant = createTenantInOrg($otherWorkspace['admin'])['tenant'];

    $contract = RentalContract::factory()
        ->for($workspace['organization'])
        ->for($assignment['tenant'], 'tenant')
        ->for($assignment['property'])
        ->for($assignment['assignment'], 'propertyAssignment')
        ->create([
            'contract_number' => 'RC-DOWNLOAD',
            'tenant_visible' => true,
        ]);

    $path = RentalContractFile::DIRECTORY.'/contract.pdf';
    Storage::disk(RentalContractFile::DISK)->put($path, 'pdf-content');

    $attachment = Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($contract, 'attachable')
        ->create([
            'document_type' => RentalContractFile::DOCUMENT_TYPE,
            'filename' => 'contract.pdf',
            'original_filename' => 'contract.pdf',
            'mime_type' => 'application/pdf',
            'disk' => RentalContractFile::DISK,
            'path' => $path,
        ]);

    actingAs($assignment['tenant']);
    get(route('tenant.rental-contracts.download', [$contract, $attachment]))
        ->assertSuccessful();

    actingAs($otherTenant);
    get(route('tenant.rental-contracts.download', [$contract, $attachment]))
        ->assertForbidden();
});

it('blocks duplicate active contracts and invalid end dates', function () {
    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);

    createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-ACTIVE-001',
    ]);

    expect(fn () => createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-ACTIVE-002',
    ]))->toThrow(ValidationException::class);

    expect(fn () => createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-BAD-DATE',
        'status' => RentalContractStatus::DRAFT->value,
        'start_date' => today()->addMonth()->toDateString(),
        'end_date' => today()->toDateString(),
    ]))->toThrow(ValidationException::class);
});

it('requires a reason to terminate rental contracts', function () {
    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);
    $contract = createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-TERM',
    ]);

    expect(fn () => app(TerminateRentalContractAction::class)->handle(
        $contract,
        $workspace['admin'],
        '',
    ))->toThrow(ValidationException::class);

    $terminated = app(TerminateRentalContractAction::class)->handle(
        $contract->fresh(),
        $workspace['admin'],
        'Tenant moved out.',
    );

    expect($terminated->status)->toBe(RentalContractStatus::TERMINATED)
        ->and($terminated->termination_reason)->toBe('Tenant moved out.');
});

it('renews a rental contract by linking the new contract to the old one', function () {
    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);
    $contract = createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-RENEW-OLD',
    ]);

    $renewed = app(RenewRentalContractAction::class)->handle($contract, $workspace['admin'], [
        'contract_number' => 'RC-RENEW-NEW',
        'start_date' => today()->addYear()->addDay()->toDateString(),
        'end_date' => today()->addYears(2)->toDateString(),
    ]);

    expect($contract->fresh()->status)->toBe(RentalContractStatus::RENEWED)
        ->and($renewed->renewed_from_contract_id)->toBe($contract->id)
        ->and($renewed->status)->toBe(RentalContractStatus::ACTIVE);
});

it('sends rental contract expiry reminders', function () {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);

    RentalContract::factory()
        ->for($workspace['organization'])
        ->for($assignment['tenant'], 'tenant')
        ->for($assignment['property'])
        ->for($assignment['assignment'], 'propertyAssignment')
        ->create([
            'contract_number' => 'RC-REMINDER',
            'end_date' => today()->addDays(30),
        ]);

    $sent = app(SendContractExpiryReminderAction::class)->handle($workspace['organization']->id);

    expect($sent)->toBe(1);

    Notification::assertSentTo($workspace['admin'], RentalContractExpiryReminderNotification::class);
});

it('expires contracts and audits rental contract changes', function () {
    Storage::fake(RentalContractFile::DISK);
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $assignment = createTenantInOrg($workspace['admin']);
    $contract = createRentalContract($workspace['admin'], [
        'tenant_id' => $assignment['tenant']->id,
        'property_id' => $assignment['property']->id,
        'property_assignment_id' => $assignment['assignment']->id,
        'contract_number' => 'RC-AUDIT',
    ]);

    app(UploadRentalContractFileAction::class)->handle(
        $contract,
        $workspace['admin'],
        UploadedFile::fake()->create('contract.pdf', 12, 'application/pdf'),
    );

    $contract->forceFill(['end_date' => today()->subDay()])->save();

    $expired = app(ExpireRentalContractsAction::class)->handle($workspace['organization']->id);

    expect($expired)->toBe(1)
        ->and($contract->fresh()->status)->toBe(RentalContractStatus::EXPIRED)
        ->and(AuditLog::query()
            ->where('subject_type', RentalContract::class)
            ->where('subject_id', $contract->id)
            ->where('action', AuditLogAction::UPDATED)
            ->exists())->toBeTrue();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createRentalContract(User $actor, array $overrides = []): RentalContract
{
    return app(CreateRentalContractAction::class)->handle($actor, [
        'contract_number' => 'RC-'.fake()->unique()->numerify('######'),
        'status' => RentalContractStatus::ACTIVE->value,
        'start_date' => today()->toDateString(),
        'end_date' => today()->addYear()->toDateString(),
        'signed_date' => today()->toDateString(),
        'rent_amount' => 850,
        'deposit_amount' => 850,
        'currency' => 'EUR',
        'tenant_visible' => true,
        'internal_notes' => null,
        'tenant_visible_notes' => null,
        ...$overrides,
    ]);
}

/**
 * @param  array{create?: bool, edit?: bool, delete?: bool}  $enabled
 * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
 */
function rentalContractPermissionMatrix(array $enabled): array
{
    $matrix = ManagerPermissionCatalog::defaultMatrix();

    $matrix['rental_contracts'] = [
        'can_create' => (bool) ($enabled['create'] ?? false),
        'can_edit' => (bool) ($enabled['edit'] ?? false),
        'can_delete' => (bool) ($enabled['delete'] ?? false),
    ];

    return $matrix;
}
