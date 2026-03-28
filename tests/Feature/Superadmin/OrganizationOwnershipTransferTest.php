<?php

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Filament\Actions\Superadmin\Organizations\TransferOrganizationOwnershipAction;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationOwnershipTransferredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('transfers ownership to a verified user in the same org', function () {
    Notification::fake();

    $superadmin = User::factory()->superadmin()->create();
    [$organization, $currentOwner] = seedOrganizationForOwnershipTransfer();

    $newOwner = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Maya Manager',
        'email' => 'maya.manager@northwind.test',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($superadmin);

    $updatedOrganization = app(TransferOrganizationOwnershipAction::class)->handle(
        $organization->fresh(),
        $newOwner,
        'Support escalation',
    );

    expect($updatedOrganization->fresh()->owner_user_id)->toBe($newOwner->id)
        ->and($newOwner->fresh()->role)->toBe(UserRole::ADMIN);

    Notification::assertSentTo($currentOwner, OrganizationOwnershipTransferredNotification::class, function (OrganizationOwnershipTransferredNotification $notification, array $channels) use ($organization, $newOwner): bool {
        return $channels === ['database']
            && $notification->organization->is($organization)
            && $notification->recipientRole === 'previous_owner'
            && $notification->newOwner->is($newOwner)
            && $notification->reason === 'Support escalation';
    });

    Notification::assertSentTo($newOwner, OrganizationOwnershipTransferredNotification::class, function (OrganizationOwnershipTransferredNotification $notification, array $channels) use ($organization, $currentOwner): bool {
        return $channels === ['database']
            && $notification->organization->is($organization)
            && $notification->recipientRole === 'new_owner'
            && $notification->previousOwner->is($currentOwner)
            && $notification->reason === 'Support escalation';
    });

    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog?->actor_user_id)->toBe($superadmin->id)
        ->and($auditLog?->metadata)->toMatchArray([
            'reason' => 'Support escalation',
            'previous_owner_user_id' => $currentOwner->id,
            'new_owner_user_id' => $newOwner->id,
        ]);
});

it('rejects ownership transfer to a user outside the organization', function () {
    [$organization] = seedOrganizationForOwnershipTransfer();

    $outsider = User::factory()->admin()->create([
        'organization_id' => Organization::factory()->create()->id,
        'email_verified_at' => now(),
    ]);

    expect(fn () => app(TransferOrganizationOwnershipAction::class)->handle(
        $organization->fresh(),
        $outsider,
        'Support escalation',
    ))->toThrow(ValidationException::class);
});

function seedOrganizationForOwnershipTransfer(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $currentOwner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
        'email_verified_at' => now(),
    ]);

    $organization->forceFill([
        'owner_user_id' => $currentOwner->id,
    ])->save();

    return [$organization->fresh(), $currentOwner->fresh()];
}
