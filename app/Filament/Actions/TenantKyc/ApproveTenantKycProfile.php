<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycProfile;
use App\Models\User;
use App\Notifications\TenantKyc\TenantKycApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveTenantKycProfile
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantKycProfile $profile, User $actor): TenantKycProfile
    {
        Gate::forUser($actor)->authorize('approve', $profile);

        return DB::transaction(function () use ($actor, $profile): TenantKycProfile {
            $before = $profile->getOriginal();
            $wasVerified = $profile->status === TenantKycProfileStatus::VERIFIED;
            $expiresAt = $profile->documents()
                ->activeForChecklist()
                ->where('status', TenantKycDocumentStatus::APPROVED)
                ->whereNotNull('expires_at')
                ->min('expires_at');

            $profile->forceFill([
                'status' => TenantKycProfileStatus::VERIFIED,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
                'expires_at' => $expiresAt,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $profile,
                [
                    'before' => $before,
                    'after' => $profile->getAttributes(),
                    'context' => ['mutation' => 'tenant_kyc_profile.approved'],
                ],
                $actor->id,
                'Tenant KYC profile approved',
            );

            $fresh = $profile->fresh(['tenant']);

            if (! $wasVerified && $fresh->tenant instanceof User) {
                $fresh->tenant->notify(new TenantKycApprovedNotification($fresh));
            }

            return $fresh;
        });
    }
}
