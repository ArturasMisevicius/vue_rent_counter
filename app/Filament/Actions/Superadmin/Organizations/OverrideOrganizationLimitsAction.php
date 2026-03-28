<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\OrganizationLimitOverride;
use App\Services\SubscriptionChecker;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OverrideOrganizationLimitsAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SubscriptionChecker $subscriptionChecker,
    ) {}

    public function handle(
        Organization $organization,
        string $dimension,
        int $value,
        string $reason,
        CarbonInterface|string $expiresAt,
    ): OrganizationLimitOverride {
        if (! in_array($dimension, OrganizationLimitOverride::SUPPORTED_DIMENSIONS, true)) {
            throw ValidationException::withMessages([
                'dimension' => __('superadmin.organizations.validation.invalid_limit_dimension'),
            ]);
        }

        $expiresAt = $expiresAt instanceof CarbonInterface ? $expiresAt : Carbon::parse((string) $expiresAt);

        if ($expiresAt->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages([
                'expires_at' => __('superadmin.organizations.validation.limit_override_expired_at'),
            ]);
        }

        $override = DB::transaction(function () use ($organization, $dimension, $value, $reason, $expiresAt): OrganizationLimitOverride {
            $override = OrganizationLimitOverride::query()->create([
                'organization_id' => $organization->id,
                'dimension' => $dimension,
                'value' => $value,
                'reason' => $reason,
                'expires_at' => $expiresAt,
                'created_by' => auth()->id(),
            ]);

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $organization,
                [
                    'reason' => $reason,
                    'dimension' => $dimension,
                    'value' => $value,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
                description: 'Organization limit override created',
            );

            return $override;
        });

        $this->subscriptionChecker->forgetOrganization($organization);

        return $override->fresh();
    }
}
