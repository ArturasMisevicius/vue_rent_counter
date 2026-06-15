<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycProfile;
use App\Models\User;

class AuditKycView
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function forProfile(TenantKycProfile $profile, User $actor, string $surface): void
    {
        $this->auditLogger->record(
            AuditLogAction::VIEWED,
            $profile,
            [
                'context' => [
                    'mutation' => 'tenant_kyc_profile.viewed',
                    'surface' => $surface,
                ],
            ],
            $actor->id,
            'Tenant KYC profile viewed',
        );
    }

    public function forTenantOverview(User $tenant): void
    {
        $profile = TenantKycProfile::query()
            ->select(['id', 'organization_id', 'tenant_id', 'status'])
            ->forOrganization((int) $tenant->organization_id)
            ->forTenant((int) $tenant->id)
            ->first();

        if ($profile === null) {
            return;
        }

        $this->forProfile($profile, $tenant, 'tenant_portal');
    }
}
