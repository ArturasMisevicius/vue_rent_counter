<?php

declare(strict_types=1);

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\AuditLogAction;
use App\Enums\SubscriptionPlan;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationPlanChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ForceOrganizationPlanChangeAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Organization $organization, SubscriptionPlan $plan, string $reason): Subscription
    {
        $organization = Organization::query()
            ->forSuperadminControlPlane()
            ->findOrFail($organization->getKey());

        $subscription = $organization->currentSubscription;

        if (! $subscription instanceof Subscription) {
            throw ValidationException::withMessages([
                'plan' => __('superadmin.organizations.validation.plan_change_requires_subscription'),
            ]);
        }

        $subscription->setRelation('organization', $organization);

        $violations = $subscription->limitViolationsForPlan($plan);

        if ($violations !== []) {
            throw ValidationException::withMessages([
                'plan' => __('superadmin.organizations.validation.plan_limit_exceeded', [
                    'dimensions' => $this->formatViolationLabels($violations),
                ]),
            ]);
        }

        $owner = $organization->owner;
        $oldPlan = $subscription->plan;

        return DB::transaction(function () use ($subscription, $plan, $reason, $organization, $owner, $oldPlan): Subscription {
            $subscription->applyPlanSnapshots($plan);
            $subscription->save();

            if (($owner instanceof User) && filled($owner->email)) {
                $owner->notify(new OrganizationPlanChangedNotification(
                    $organization,
                    $oldPlan,
                    $plan,
                    $reason,
                ));
            }

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $organization,
                [
                    'reason' => $reason,
                    'old_plan' => $oldPlan->value,
                    'new_plan' => $plan->value,
                ],
                description: 'Organization plan changed',
            );

            return $subscription->fresh();
        });
    }

    /**
     * @param  list<string>  $violations
     */
    private function formatViolationLabels(array $violations): string
    {
        return collect($violations)
            ->map(fn (string $dimension): string => match ($dimension) {
                'properties' => __('superadmin.organizations.overview.usage_labels.properties'),
                'tenants' => __('superadmin.organizations.overview.usage_labels.tenants'),
                'meters' => __('superadmin.organizations.overview.usage_labels.meters'),
                'invoices' => __('superadmin.organizations.overview.usage_labels.invoices'),
                default => $dimension,
            })
            ->implode(', ');
    }
}
