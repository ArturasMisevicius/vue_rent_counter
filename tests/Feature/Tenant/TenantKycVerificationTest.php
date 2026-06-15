<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Enums\ManagerMembershipStatus;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use App\Enums\TenantKycProfileStatus;
use App\Enums\UserRole;
use App\Filament\Actions\TenantKyc\ApproveKycDocument;
use App\Filament\Actions\TenantKyc\ExpireKycDocuments;
use App\Filament\Actions\TenantKyc\RejectKycDocument;
use App\Filament\Actions\TenantKyc\SubmitTenantKycDocument;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Livewire\Tenant\Verification;
use App\Models\AuditLog;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\OrganizationUser;
use App\Models\Property;
use App\Models\TenantKycDocument;
use App\Models\User;
use App\Notifications\TenantDocuments\TenantKycDocumentUploadedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Notification::fake();
});

it('lets tenants upload their own KYC document', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);

    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    expect($document->tenant_id)->toBe($workspace['tenant']->id)
        ->and($document->organization_id)->toBe($workspace['organization']->id)
        ->and($document->status)->toBe(TenantKycDocumentStatus::PENDING_REVIEW)
        ->and(Storage::disk('local')->exists($document->fileDocument->file_path))->toBeTrue();
});

it('notifies admin and manager when tenants upload KYC documents', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);
    OrganizationUser::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
        'invited_by_user_id' => $workspace['admin']->id,
        'accepted_at' => now(),
        'left_at' => null,
    ]);

    submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    Notification::assertSentTo($workspace['admin'], TenantKycDocumentUploadedNotification::class);
    Notification::assertSentTo($manager, TenantKycDocumentUploadedNotification::class);
});

it('blocks tenants from uploading KYC for another tenant', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(fn () => app(SubmitTenantKycDocument::class)->handle($workspace['tenant'], [
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $otherTenant->id,
        'document_type' => TenantKycDocumentType::IDENTITY_CARD->value,
        'expires_at' => now()->addYear()->toDateString(),
    ], UploadedFile::fake()->create('id.pdf', 24, 'application/pdf')))
        ->toThrow(ValidationException::class);
});

it('shows tenant KYC status and rejection reason without internal notes', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    app(RejectKycDocument::class)->handle($document, $workspace['admin'], [
        'rejection_reason' => 'Please upload a clearer scan.',
        'internal_note' => 'Fraud review note for admins only.',
    ]);

    actingAs($workspace['tenant']);

    Livewire::test(Verification::class)
        ->assertSee(TenantKycProfileStatus::REJECTED->label())
        ->assertSee('Please upload a clearer scan.')
        ->assertDontSee('Fraud review note for admins only.');
});

it('lets admins review KYC in their own organization only', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $otherWorkspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    app(ApproveKycDocument::class)->handle($document, $workspace['admin']);

    expect($document->fresh()->status)->toBe(TenantKycDocumentStatus::APPROVED);

    $otherDocument = submitKycDocument($otherWorkspace, $otherWorkspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    expect(fn () => app(ApproveKycDocument::class)->handle($otherDocument, $workspace['admin']))
        ->toThrow(AuthorizationException::class);
});

it('makes manager KYC review depend on tenant document permission', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);
    $manager = User::factory()->manager()->create([
        'organization_id' => $workspace['organization']->id,
    ]);
    OrganizationUser::factory()->create([
        'organization_id' => $workspace['organization']->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
        'invited_by_user_id' => $workspace['admin']->id,
        'accepted_at' => now(),
        'left_at' => null,
    ]);

    expect(Gate::forUser($manager)->allows('approve', $document))->toBeFalse();

    ManagerPermission::syncForManager($manager, $workspace['organization'], [
        ...ManagerPermissionCatalog::defaultMatrix(),
        'tenant_documents' => [
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => false,
        ],
    ]);
    ManagerPermissionService::flushCache();

    expect(Gate::forUser($manager)->allows('approve', $document))->toBeTrue();
});

it('requires a tenant-visible reason when rejecting KYC', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    expect(fn () => app(RejectKycDocument::class)->handle($document, $workspace['admin'], [
        'rejection_reason' => '',
    ]))->toThrow(ValidationException::class);
});

it('verifies the profile when all required documents are approved', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    app(ApproveKycDocument::class)->handle($document, $workspace['admin']);

    expect($document->profile->fresh()->status)->toBe(TenantKycProfileStatus::VERIFIED);
});

it('expires the profile when a required document expires', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    app(ApproveKycDocument::class)->handle($document, $workspace['admin']);

    $document->forceFill([
        'expires_at' => now()->subDay(),
        'status' => TenantKycDocumentStatus::APPROVED,
    ])->save();

    $expired = app(ExpireKycDocuments::class)->handle($workspace['organization']->id);

    expect($expired)->toBe(1)
        ->and($document->fresh()->status)->toBe(TenantKycDocumentStatus::EXPIRED)
        ->and($document->profile->fresh()->status)->toBe(TenantKycProfileStatus::EXPIRED);
});

it('requires authorization for KYC downloads and writes audit logs', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    actingAs($otherTenant);

    get(route('tenant.kyc-documents.download', $document))
        ->assertForbidden();

    actingAs($workspace['tenant']);

    get(route('tenant.kyc-documents.download', $document))
        ->assertOk();

    expect(AuditLog::query()
        ->forSubject($document)
        ->forAction(AuditLogAction::EXPORTED)
        ->exists())->toBeTrue();
});

it('keeps KYC files private from public storage URLs', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD);

    get('/storage/'.$document->fileDocument->file_path)
        ->assertForbidden();
});

it('encrypts sensitive KYC document numbers at rest', function (): void {
    $workspace = tenantKycWorkspace([TenantKycDocumentType::IDENTITY_CARD]);
    $document = submitKycDocument($workspace, $workspace['tenant'], TenantKycDocumentType::IDENTITY_CARD, [
        'document_number_encrypted' => 'ID-123456',
    ]);

    $fresh = $document->fresh();

    expect($fresh->document_number_encrypted)->toBe('ID-123456')
        ->and($fresh->getRawOriginal('document_number_encrypted'))->not->toBe('ID-123456');
});

/**
 * @param  list<TenantKycDocumentType>  $requiredTypes
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     property: Property
 * }
 */
function tenantKycWorkspace(array $requiredTypes): array
{
    $workspace = createOrgWithAdmin();
    $tenantFixture = createTenantInOrg($workspace['admin']);

    OrganizationSetting::factory()->for($workspace['organization'])->create([
        'kyc_required' => true,
        'required_document_types' => collect($requiredTypes)->map->value->all(),
        'require_expiry_date' => true,
    ]);

    return [
        'organization' => $workspace['organization'],
        'admin' => $workspace['admin'],
        'tenant' => $tenantFixture['tenant'],
        'property' => $tenantFixture['property'],
    ];
}

/**
 * @param  array{organization: Organization, admin: User, tenant: User, property: Property}  $workspace
 * @param  array<string, mixed>  $overrides
 */
function submitKycDocument(
    array $workspace,
    User $actor,
    TenantKycDocumentType $type,
    array $overrides = [],
): TenantKycDocument {
    return app(SubmitTenantKycDocument::class)->handle($actor, [
        'organization_id' => $workspace['organization']->id,
        'tenant_id' => $workspace['tenant']->id,
        'document_type' => $type->value,
        'document_number_encrypted' => null,
        'issued_country' => 'LT',
        'issued_at' => now()->subYear()->toDateString(),
        'expires_at' => now()->addYear()->toDateString(),
        ...$overrides,
    ], UploadedFile::fake()->create($type->value.'.pdf', 24, 'application/pdf'));
}
