<?php

declare(strict_types=1);

namespace App\Jobs\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\OrganizationLimitOverride;
use App\Services\SubscriptionChecker;

final class ExpireOrganizationLimitOverridesJob
{
    public function handle(): void
    {
        $auditLogger = app(AuditLogger::class);
        $subscriptionChecker = app(SubscriptionChecker::class);

        OrganizationLimitOverride::query()
            ->select(['id', 'organization_id', 'dimension', 'value', 'reason', 'expires_at'])
            ->with(['organization:id,name,slug,status,owner_user_id'])
            ->expired()
            ->orderBy('id')
            ->chunkById(100, function ($overrides) use ($auditLogger, $subscriptionChecker): void {
                foreach ($overrides as $override) {
                    if ($override->organization !== null) {
                        $auditLogger->record(
                            AuditLogAction::UPDATED,
                            $override->organization,
                            [
                                'reason' => $override->reason,
                                'dimension' => $override->dimension,
                                'value' => $override->value,
                                'expires_at' => $override->expires_at?->toIso8601String(),
                            ],
                            description: 'Organization limit override expired',
                        );
                    }

                    $subscriptionChecker->forgetOrganization($override->organization_id);
                    $override->delete();
                }
            });
    }
}
