<?php

use App\Enums\AuditLogAction;
use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

uses(RefreshDatabase::class);

it('restores the superadmin after an impersonation session expires on the next panel request', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $this->actingAs($owner)
        ->withSession(expiredImpersonationSessionFor($superadmin, $owner))
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertSuccessful();

    $this->assertAuthenticatedAs($superadmin);

    expect(session()->missing('impersonator_id'))->toBeTrue()
        ->and(session()->missing('impersonator_name'))->toBeTrue()
        ->and(session()->missing('impersonator_email'))->toBeTrue()
        ->and(session()->missing('impersonation_session_id'))->toBeTrue()
        ->and(session()->missing('impersonation_started_at'))->toBeTrue()
        ->and(session()->missing('impersonation_expires_at'))->toBeTrue()
        ->and(session()->missing('impersonated_user_id'))->toBeTrue()
        ->and(session()->missing('impersonated_user_name'))->toBeTrue()
        ->and(session()->missing('impersonated_user_email'))->toBeTrue();
});

it('blocks impersonation when the organization has an active critical security incident', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    SecurityViolation::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $owner->id,
        'type' => SecurityViolationType::IMPERSONATION,
        'severity' => SecurityViolationSeverity::CRITICAL,
        'resolved_at' => null,
    ]);

    expect(fn () => app(StartOrganizationImpersonationAction::class)->handle($superadmin, $owner))
        ->toThrow(AccessDeniedHttpException::class);
});

it('records impersonation metadata on downstream audit entries and renders dual attribution in the audit feed', function () {
    $superadmin = User::factory()->superadmin()->create([
        'name' => 'Support Root',
    ]);
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);
    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $this->actingAs($superadmin);

    app(StartOrganizationImpersonationAction::class)->handle($superadmin, $owner);

    $organization->forceFill([
        'name' => 'Northwind Heights',
    ])->save();

    $auditLog = AuditLog::query()
        ->forSubject($organization)
        ->forAction(AuditLogAction::UPDATED)
        ->latest('id')
        ->firstOrFail();

    expect(data_get($auditLog->metadata, 'impersonation.session_id'))->toBeString()
        ->and(data_get($auditLog->metadata, 'impersonation.impersonator.id'))->toBe($superadmin->id)
        ->and(data_get($auditLog->metadata, 'impersonation.impersonator.name'))->toBe($superadmin->name)
        ->and(data_get($auditLog->metadata, 'impersonation.impersonated_user.id'))->toBe($owner->id)
        ->and(data_get($auditLog->metadata, 'impersonation.impersonated_user.name'))->toBe($owner->name);

    $this->actingAs($superadmin);

    Livewire::test(ListAuditLogs::class)
        ->assertTableColumnStateSet('actor_summary', "Superadmin (impersonating {$owner->name})", $auditLog);
});

/**
 * @return array<string, int|string>
 */
function expiredImpersonationSessionFor(User $impersonator, User $target): array
{
    return [
        'impersonator_id' => $impersonator->id,
        'impersonator_name' => $impersonator->name,
        'impersonator_email' => $impersonator->email,
        'impersonated_user_id' => $target->id,
        'impersonated_user_name' => $target->name,
        'impersonated_user_email' => $target->email,
        'impersonation_session_id' => (string) Str::uuid(),
        'impersonation_started_at' => now()->subHour()->subMinute()->toIso8601String(),
        'impersonation_expires_at' => now()->subMinute()->toIso8601String(),
    ];
}
