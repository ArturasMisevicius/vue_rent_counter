<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Enums\TenantKycProfileStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\TenantKyc\RejectTenantKycProfileRequest;
use App\Models\TenantKycProfile;
use App\Models\User;
use App\Notifications\TenantKyc\TenantKycRejectedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RejectTenantKycProfile
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(TenantKycProfile $profile, User $actor, array $data): TenantKycProfile
    {
        Gate::forUser($actor)->authorize('reject', $profile);

        $validated = (new RejectTenantKycProfileRequest)->validatePayload($data, $actor);

        return DB::transaction(function () use ($actor, $profile, $validated): TenantKycProfile {
            $before = $profile->getOriginal();

            $profile->forceFill([
                'status' => TenantKycProfileStatus::REJECTED,
                'reviewed_by_user_id' => $actor->id,
                'reviewed_at' => now(),
                'approved_at' => null,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $profile,
                [
                    'before' => $before,
                    'after' => $profile->getAttributes(),
                    'context' => ['mutation' => 'tenant_kyc_profile.rejected'],
                ],
                $actor->id,
                'Tenant KYC profile rejected',
            );

            $fresh = $profile->fresh(['tenant']);

            if ($fresh->tenant instanceof User) {
                $fresh->tenant->notify(new TenantKycRejectedNotification($fresh));
            }

            return $fresh;
        });
    }
}
