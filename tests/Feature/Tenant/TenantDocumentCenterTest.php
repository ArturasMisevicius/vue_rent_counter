<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Filament\Actions\Admin\TenantDocuments\ExpireTenantDocuments;
use App\Filament\Actions\Admin\TenantDocuments\ReplaceTenantDocumentFile;
use App\Filament\Actions\Admin\TenantDocuments\ToggleTenantDocumentVisibility;
use App\Filament\Actions\Admin\TenantDocuments\UploadTenantDocument;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Livewire\Tenant\Documents;
use App\Models\AuditLog;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Notification::fake();
});

it('lets tenants see their own visible documents', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'title' => 'Signed Lease',
        'description_for_tenant' => 'Your signed lease document.',
    ]);

    actingAs($workspace['tenant']);

    Livewire::test(Documents::class)
        ->assertSee($document->title)
        ->assertSee('Your signed lease document.');
});

it('hides internal-only documents and internal notes from tenants', function (): void {
    $workspace = tenantDocumentWorkspace();
    tenantDocumentFor($workspace, [
        'title' => 'Internal Risk Note',
        'internal_note' => 'Do not show this note to tenants.',
        'tenant_visible' => false,
        'description_for_tenant' => null,
    ]);

    actingAs($workspace['tenant']);

    Livewire::test(Documents::class)
        ->assertDontSee('Internal Risk Note')
        ->assertDontSee('Do not show this note to tenants.');
});

it('does not show another tenant document', function (): void {
    $workspace = tenantDocumentWorkspace();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    tenantDocumentFor($workspace, [
        'tenant_id' => $otherTenant->id,
        'title' => 'Other Tenant Contract',
    ]);

    actingAs($workspace['tenant']);

    Livewire::test(Documents::class)
        ->assertDontSee('Other Tenant Contract');
});

it('blocks tenants from downloading another tenant file', function (): void {
    $workspace = tenantDocumentWorkspace();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);
    $document = tenantDocumentFor($workspace, [
        'tenant_id' => $otherTenant->id,
        'file_path' => 'tenant-documents/other.pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'private document');

    actingAs($workspace['tenant']);

    get(route('tenant.documents.download', $document))
        ->assertForbidden();
});

it('lets admins upload documents inside their organization', function (): void {
    $workspace = tenantDocumentWorkspace();

    $document = app(UploadTenantDocument::class)->handle($workspace['admin'], [
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $workspace['tenant']->id,
        'property_id' => $workspace['property']->id,
        'document_type' => TenantDocumentType::RENTAL_CONTRACT->value,
        'title' => 'Uploaded Contract',
        'description_for_tenant' => 'Contract shared with the tenant.',
        'status' => TenantDocumentStatus::ACTIVE->value,
        'tenant_visible' => true,
    ], UploadedFile::fake()->create('contract.pdf', 32, 'application/pdf'));

    expect($document->organization_id)->toBe($workspace['organization']->id)
        ->and($document->tenant_id)->toBe($workspace['tenant']->id)
        ->and(Storage::disk('local')->exists($document->file_path))->toBeTrue();
});

it('blocks admins from accessing another organization document', function (): void {
    $workspace = tenantDocumentWorkspace();
    $otherWorkspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($otherWorkspace);

    expect(Gate::forUser($workspace['admin'])->allows('view', $document))->toBeFalse();
});

it('makes manager document writes depend on tenant document permission', function (): void {
    $workspace = tenantDocumentWorkspace();
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(Gate::forUser($manager)->allows('create', TenantDocument::class))->toBeFalse();

    ManagerPermission::syncForManager($manager, $workspace['organization'], [
        ...ManagerPermissionCatalog::defaultMatrix(),
        'tenant_documents' => [
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ],
    ]);
    ManagerPermissionService::flushCache();

    expect(Gate::forUser($manager)->allows('create', TenantDocument::class))->toBeTrue();
});

it('requires authentication before document download', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace);

    get(route('tenant.documents.download', $document))
        ->assertRedirect(route('login'));
});

it('keeps private files unavailable from public storage URLs', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'file_path' => 'tenant-documents/private-contract.pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'private document');

    get('/storage/'.$document->file_path)
        ->assertForbidden();
});

it('audits every authorized download', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'file_path' => 'tenant-documents/downloadable.pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'downloadable document');

    actingAs($workspace['tenant']);

    get(route('tenant.documents.download', $document))
        ->assertOk();

    expect(AuditLog::query()
        ->forSubject($document)
        ->forAction(AuditLogAction::EXPORTED)
        ->exists())->toBeTrue();
});

it('audits tenant visibility changes', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'tenant_visible' => false,
        'description_for_tenant' => 'Safe tenant description.',
    ]);

    app(ToggleTenantDocumentVisibility::class)->handle($document, $workspace['admin'], true);

    expect($document->fresh()->tenant_visible)->toBeTrue()
        ->and(AuditLog::query()->forSubject($document)->forAction(AuditLogAction::UPDATED)->exists())->toBeTrue();
});

it('audits file replacement and keeps the previous private file', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'file_path' => 'tenant-documents/original.pdf',
        'original_filename' => 'original.pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'original document');

    app(ReplaceTenantDocumentFile::class)->handle(
        $document,
        $workspace['admin'],
        UploadedFile::fake()->create('replacement.pdf', 24, 'application/pdf'),
    );

    $fresh = $document->fresh();

    expect($fresh->file_path)->not->toBe('tenant-documents/original.pdf')
        ->and(Storage::disk('local')->exists('tenant-documents/original.pdf'))->toBeTrue()
        ->and(AuditLog::query()->forSubject($document)->forAction(AuditLogAction::UPDATED)->exists())->toBeTrue();
});

it('marks expired documents as expired', function (): void {
    $workspace = tenantDocumentWorkspace();
    $document = tenantDocumentFor($workspace, [
        'status' => TenantDocumentStatus::ACTIVE,
        'expires_at' => now()->subDay(),
    ]);

    $expired = app(ExpireTenantDocuments::class)->handle($workspace['organization']->id);

    expect($expired)->toBe(1)
        ->and($document->fresh()->status)->toBe(TenantDocumentStatus::EXPIRED);
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     property: \App\Models\Property
 * }
 */
function tenantDocumentWorkspace(): array
{
    $workspace = createOrgWithAdmin();
    $tenantFixture = createTenantInOrg($workspace['admin']);

    return [
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'tenant' => $tenantFixture['tenant'],
        'property' => $tenantFixture['property'],
    ];
}

/**
 * @param  array{organization: Organization, admin: User, tenant: User, property: \App\Models\Property}  $workspace
 * @param  array<string, mixed>  $overrides
 */
function tenantDocumentFor(array $workspace, array $overrides = []): TenantDocument
{
    $path = $overrides['file_path'] ?? 'tenant-documents/'.fake()->uuid().'.pdf';

    Storage::disk('local')->put((string) $path, 'tenant document');

    return TenantDocument::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $workspace['tenant']->id,
        'property_id' => $workspace['property']->id,
        'file_path' => $path,
        'uploaded_by_user_id' => $workspace['admin']->id,
        ...$overrides,
    ]);
}
