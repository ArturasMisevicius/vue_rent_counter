<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationOwnershipTransferredNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransferOrganizationOwnershipAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Organization $organization, User $newOwner, string $reason): Organization
    {
        $organization = Organization::query()
            ->forSuperadminControlPlane()
            ->findOrFail($organization->getKey());

        $currentOwner = $organization->owner;

        if ($newOwner->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'new_owner_user_id' => __('superadmin.organizations.validation.transfer_owner_must_belong_to_org'),
            ]);
        }

        if ($newOwner->email_verified_at === null) {
            throw ValidationException::withMessages([
                'new_owner_user_id' => __('superadmin.organizations.validation.transfer_owner_must_be_verified'),
            ]);
        }

        return DB::transaction(function () use ($organization, $newOwner, $currentOwner, $reason): Organization {
            $newOwner->forceFill([
                'role' => UserRole::ADMIN,
            ])->save();

            $organization->forceFill([
                'owner_user_id' => $newOwner->id,
            ])->save();

            $freshOrganization = $organization->fresh(['owner:id,name,email']);

            if ($currentOwner instanceof User) {
                $currentOwner->notify(new OrganizationOwnershipTransferredNotification(
                    $freshOrganization,
                    $currentOwner,
                    $newOwner,
                    'previous_owner',
                    $reason,
                ));
            }

            $newOwner->notify(new OrganizationOwnershipTransferredNotification(
                $freshOrganization,
                $currentOwner instanceof User ? $currentOwner : $newOwner,
                $newOwner,
                'new_owner',
                $reason,
            ));

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshOrganization,
                [
                    'reason' => $reason,
                    'previous_owner_user_id' => $currentOwner?->id,
                    'new_owner_user_id' => $newOwner->id,
                ],
                description: 'Organization ownership transferred',
            );

            return $freshOrganization;
        });
    }
}
