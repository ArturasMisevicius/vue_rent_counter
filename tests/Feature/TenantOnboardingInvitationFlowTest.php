<?php

declare(strict_types=1);

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\DisableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\EnableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\ResendTenantInvitation;
use App\Filament\Actions\Admin\Tenants\RevokeTenantInvitation;
use App\Filament\Actions\Admin\Tenants\SendTenantInvitation;
use App\Filament\Actions\Auth\AcceptTenantInvitation;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use App\Notifications\TenantPortalActivatedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\TenantPortalFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('lets an admin send a tenant invitation while storing only hashed tokens', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $tenant = onboardingTenant($workspace['organization']);

    $invitation = app(SendTenantInvitation::class)->handle($workspace['admin'], $tenant, 14);
    $rawToken = $invitation->acceptanceToken;

    expect($rawToken)->toBeString()->toHaveLength(64);

    $storedInvitation = $invitation->fresh();

    expect($storedInvitation->token_hash)
        ->toBe(OrganizationInvitation::hashToken($rawToken))
        ->not->toBe($rawToken)
        ->and($storedInvitation->token)
        ->toBe(OrganizationInvitation::hashToken($rawToken))
        ->not->toBe($rawToken)
        ->and($storedInvitation->tenant_id)->toBe($tenant->id)
        ->and($storedInvitation->invited_by_user_id)->toBe($workspace['admin']->id)
        ->and($storedInvitation->sent_at)->not->toBeNull()
        ->and($storedInvitation->expires_at?->isSameDay(now()->addDays(14)))->toBeTrue();

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);

    expect(auditMutations())->toContain('tenant_invitation.sent');
});

it('blocks cross-organization admins and tenants from sending tenant invitations', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $otherWorkspace = createOrgWithAdmin();
    $tenant = onboardingTenant($workspace['organization']);
    $tenantActor = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(fn () => app(SendTenantInvitation::class)->handle($otherWorkspace['admin'], $tenant))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(SendTenantInvitation::class)->handle($tenantActor, $tenant))
        ->toThrow(AuthorizationException::class);
});

it('allows managers to invite tenants only when their policy permission allows it', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $organization = $workspace['organization'];
    $admin = $workspace['admin'];
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = onboardingTenant($organization);

    expect(fn () => app(SendTenantInvitation::class)->handle($manager, $tenant))
        ->toThrow(AuthorizationException::class);

    $matrix = ManagerPermissionCatalog::defaultMatrix();
    $matrix['tenants']['can_create'] = true;

    app(ManagerPermissionService::class)->saveMatrix($manager, $organization, $matrix, $admin);

    $invitation = app(SendTenantInvitation::class)->handle($manager->fresh(), $tenant->fresh());

    expect($invitation->tenant_id)->toBe($tenant->id);
});

it('lets a tenant accept a valid invitation and activates their portal account', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $tenant = onboardingTenant($workspace['organization'], [
        'name' => 'Pending Resident',
        'email' => 'resident@example.com',
    ]);

    $invitation = app(SendTenantInvitation::class)->handle($workspace['admin'], $tenant);

    actingAs($workspace['admin']);
    auth()->logout();

    post(route('invitation.store', $invitation->acceptanceToken), [
        'name' => 'Accepted Resident',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect();

    $tenant->refresh();
    $invitation->refresh();

    expect($tenant->name)->toBe('Accepted Resident')
        ->and($tenant->status)->toBe(UserStatus::ACTIVE)
        ->and($tenant->tenant_status)->toBe(TenantStatus::ACTIVE)
        ->and($tenant->portal_access_enabled)->toBeTrue()
        ->and($tenant->email_verified_at)->not->toBeNull()
        ->and(Hash::check('new-secure-password', $tenant->password))->toBeTrue()
        ->and($invitation->accepted_at)->not->toBeNull();

    Notification::assertSentTo($tenant, TenantPortalActivatedNotification::class);

    expect(auditMutations())
        ->toContain('tenant_invitation.sent')
        ->toContain('tenant_invitation.accepted')
        ->toContain('tenant_portal.activated');
});

it('rejects accepted, expired, revoked, and inactive tenant invitations', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $tenant = onboardingTenant($workspace['organization']);
    $invitation = app(SendTenantInvitation::class)->handle($workspace['admin'], $tenant);

    app(AcceptTenantInvitation::class)->handle($invitation, [
        'name' => $tenant->name,
        'password' => 'new-secure-password',
    ], 'en');

    expect(fn () => app(AcceptTenantInvitation::class)->handle($invitation->fresh(), [
        'name' => $tenant->name,
        'password' => 'another-password',
    ], 'en'))->toThrow(ValidationException::class);

    $expiredInvitation = tenantInvitationFor(
        tenant: onboardingTenant($workspace['organization'], ['email' => 'expired@example.com']),
        inviter: $workspace['admin'],
        expiresAt: now()->subMinute(),
    );

    expect(fn () => app(AcceptTenantInvitation::class)->handle($expiredInvitation, [
        'name' => 'Expired Tenant',
        'password' => 'new-secure-password',
    ], 'en'))->toThrow(ValidationException::class);

    $revokedTenant = onboardingTenant($workspace['organization'], ['email' => 'revoked@example.com']);
    $revokedInvitation = app(SendTenantInvitation::class)->handle($workspace['admin'], $revokedTenant);

    app(RevokeTenantInvitation::class)->handle($workspace['admin'], $revokedInvitation);

    expect(fn () => app(AcceptTenantInvitation::class)->handle($revokedInvitation->fresh(), [
        'name' => 'Revoked Tenant',
        'password' => 'new-secure-password',
    ], 'en'))->toThrow(ValidationException::class);

    $movedOutTenant = onboardingTenant($workspace['organization'], [
        'email' => 'moved-out@example.com',
        'tenant_status' => TenantStatus::MOVED_OUT,
    ]);
    $oldInvitation = tenantInvitationFor($movedOutTenant, $workspace['admin']);

    expect(fn () => app(AcceptTenantInvitation::class)->handle($oldInvitation, [
        'name' => 'Moved Out Tenant',
        'password' => 'new-secure-password',
    ], 'en'))->toThrow(ValidationException::class);
});

it('invalidates the old single-use token when a tenant invitation is resent', function (): void {
    Notification::fake();

    $workspace = createOrgWithAdmin();
    $tenant = onboardingTenant($workspace['organization']);
    $firstInvitation = app(SendTenantInvitation::class)->handle($workspace['admin'], $tenant);
    $oldToken = $firstInvitation->acceptanceToken;

    $resentInvitation = app(ResendTenantInvitation::class)->handle($workspace['admin'], $tenant);

    expect($resentInvitation->acceptanceToken)->not->toBe($oldToken)
        ->and($firstInvitation->fresh()->isRevoked())->toBeTrue()
        ->and(OrganizationInvitation::query()->forToken($oldToken)->first()?->isRevoked())->toBeTrue();

    app(AcceptTenantInvitation::class)->handle($resentInvitation, [
        'name' => $tenant->name,
        'password' => 'new-secure-password',
    ], 'en');

    expect($tenant->fresh()->portal_access_enabled)->toBeTrue();
});

it('blocks disabled tenant portal accounts and keeps tenant data isolated', function (): void {
    Storage::fake(config('filesystems.default', 'local'));

    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();
    $otherFixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $foreignInvoice = Invoice::factory()
        ->for($otherFixture->organization)
        ->for($otherFixture->property)
        ->for($otherFixture->user, 'tenant')
        ->create([
            'document_path' => 'tenant-invoices/foreign.pdf',
        ]);

    Storage::disk(config('filesystems.default', 'local'))->put('tenant-invoices/foreign.pdf', 'pdf-content');

    actingAs($fixture->user)
        ->get(route('tenant.invoices.download', $foreignInvoice))
        ->assertForbidden();

    app(DisableTenantPortalAccess::class)->handle(
        User::factory()->admin()->create(['organization_id' => $fixture->organization->id]),
        $fixture->user,
    );

    actingAs($fixture->user->fresh())
        ->get(route('tenant.home'))
        ->assertForbidden();

    app(EnableTenantPortalAccess::class)->handle(
        User::factory()->admin()->create(['organization_id' => $fixture->organization->id]),
        $fixture->user->fresh(),
    );

    expect($fixture->user->fresh()->canAccessTenantPortal())->toBeTrue()
        ->and(auditMutations())
        ->toContain('tenant_portal.disabled')
        ->toContain('tenant_portal.enabled');
});

function onboardingTenant(Organization $organization, array $attributes = []): User
{
    return User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::INACTIVE,
        'tenant_status' => TenantStatus::DRAFT,
        'portal_access_enabled' => false,
        'email_verified_at' => null,
        ...$attributes,
    ]);
}

function tenantInvitationFor(User $tenant, User $inviter, mixed $expiresAt = null): OrganizationInvitation
{
    $rawToken = OrganizationInvitation::issueToken();
    $hash = OrganizationInvitation::hashToken($rawToken);

    $invitation = OrganizationInvitation::query()->create([
        'organization_id' => $tenant->organization_id,
        'tenant_id' => $tenant->id,
        'inviter_user_id' => $inviter->id,
        'invited_by_user_id' => $inviter->id,
        'email' => $tenant->email,
        'role' => UserRole::TENANT,
        'full_name' => $tenant->name,
        'token' => $hash,
        'token_hash' => $hash,
        'sent_at' => now(),
        'expires_at' => $expiresAt ?? now()->addDays(7),
        'accepted_at' => null,
        'revoked_at' => null,
    ]);

    $invitation->acceptanceToken = $rawToken;

    return $invitation;
}

/**
 * @return array<int, string|null>
 */
function auditMutations(): array
{
    return AuditLog::query()
        ->get()
        ->map(fn (AuditLog $log): ?string => $log->metadata['context']['mutation'] ?? null)
        ->filter()
        ->values()
        ->all();
}
