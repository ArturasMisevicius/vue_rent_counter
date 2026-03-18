<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SubscriptionAccessMode;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Support\Admin\SubscriptionEnforcement\SubscriptionAccessState;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class SubscriptionChecker
{
    /**
     * @var array<string, array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }>
     */
    private array $userSnapshots = [];

    /**
     * @var array<int, array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }>
     */
    private array $organizationSnapshots = [];

    public function canCreateProperty(User $user): bool
    {
        return ! $this->accessState($user)->blocksCreation('properties');
    }

    public function canCreateTenant(User $user): bool
    {
        return ! $this->accessState($user)->blocksCreation('tenants');
    }

    public function getRemainingProperties(User $user): int
    {
        $snapshot = $this->cachedSnapshotForUser($user);

        return $this->remainingCount(
            $snapshot['limits']['properties'],
            $snapshot['usage']['properties'],
        );
    }

    public function getRemainingTenants(User $user): int
    {
        $snapshot = $this->cachedSnapshotForUser($user);

        return $this->remainingCount(
            $snapshot['limits']['tenants'],
            $snapshot['usage']['tenants'],
        );
    }

    public function accessState(User $user): SubscriptionAccessState
    {
        return $this->stateFromSnapshot($this->cachedSnapshotForUser($user));
    }

    public function accessStateForOrganization(Organization|int|null $organization): SubscriptionAccessState
    {
        $organizationId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->stateFromSnapshot($this->snapshotForOrganizationId($organizationId));
    }

    /**
     * @return array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }
     */
    private function cachedSnapshotForUser(User $user): array
    {
        if ($user->id === null) {
            return $this->defaultSnapshot();
        }

        $cacheKey = "subscription-checker:user:{$user->id}";

        return $this->userSnapshots[$cacheKey] ??= Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): array => $this->snapshotForOrganizationId($user->organization_id),
        );
    }

    /**
     * @return array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }
     */
    private function snapshotForOrganizationId(?int $organizationId): array
    {
        if ($organizationId === null) {
            return $this->defaultSnapshot();
        }

        if (array_key_exists($organizationId, $this->organizationSnapshots)) {
            return $this->organizationSnapshots[$organizationId];
        }

        $organization = Organization::query()
            ->select(['id'])
            ->whereKey($organizationId)
            ->withCount([
                'properties',
                'meters',
                'invoices',
                'users as tenants_count' => fn (Builder $query): Builder => $query
                    ->where('role', UserRole::TENANT),
            ])
            ->addSelect([
                'subscription_status_value' => Subscription::query()
                    ->select('status')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
                'subscription_expires_at_value' => Subscription::query()
                    ->select('expires_at')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
                'property_limit_value' => Subscription::query()
                    ->select('property_limit_snapshot')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
                'tenant_limit_value' => Subscription::query()
                    ->select('tenant_limit_snapshot')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
                'meter_limit_value' => Subscription::query()
                    ->select('meter_limit_snapshot')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
                'invoice_limit_value' => Subscription::query()
                    ->select('invoice_limit_snapshot')
                    ->forOrganization($organizationId)
                    ->latestFirst()
                    ->limit(1),
            ])
            ->first();

        if ($organization === null) {
            return $this->defaultSnapshot();
        }

        $status = SubscriptionStatus::tryFrom((string) ($organization->getAttribute('subscription_status_value') ?? ''));
        $expiresAtValue = $organization->getAttribute('subscription_expires_at_value');
        $expiresAt = $expiresAtValue !== null
            ? CarbonImmutable::parse((string) $expiresAtValue)
            : null;
        $graceEndsAt = $expiresAt?->addDays((int) config('tenanto.subscription.grace_period_days', 7));
        $usage = [
            'properties' => (int) ($organization->getAttribute('properties_count') ?? 0),
            'tenants' => (int) ($organization->getAttribute('tenants_count') ?? 0),
            'meters' => (int) ($organization->getAttribute('meters_count') ?? 0),
            'invoices' => (int) ($organization->getAttribute('invoices_count') ?? 0),
        ];
        $limits = [
            'properties' => $this->integerOrNull($organization->getAttribute('property_limit_value')),
            'tenants' => $this->integerOrNull($organization->getAttribute('tenant_limit_value')),
            'meters' => $this->integerOrNull($organization->getAttribute('meter_limit_value')),
            'invoices' => $this->integerOrNull($organization->getAttribute('invoice_limit_value')),
        ];
        $limitHits = collect($limits)
            ->filter(fn (?int $limit, string $resource): bool => $limit !== null && ($usage[$resource] ?? 0) >= $limit)
            ->keys()
            ->values()
            ->all();

        return $this->organizationSnapshots[$organizationId] = [
            'mode' => $this->modeFor($status, $expiresAt, $limitHits)->value,
            'limit_hits' => $limitHits,
            'grace_ends_at' => $graceEndsAt?->toIso8601String(),
            'usage' => $usage,
            'limits' => $limits,
        ];
    }

    /**
     * @param  array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }  $snapshot
     */
    private function stateFromSnapshot(array $snapshot): SubscriptionAccessState
    {
        return new SubscriptionAccessState(
            mode: SubscriptionAccessMode::from($snapshot['mode']),
            limitHits: $snapshot['limit_hits'],
            graceEndsAt: $snapshot['grace_ends_at'] !== null
                ? CarbonImmutable::parse($snapshot['grace_ends_at'])
                : null,
            usage: $snapshot['usage'],
            limits: $snapshot['limits'],
        );
    }

    /**
     * @param  list<string>  $limitHits
     */
    private function modeFor(
        ?SubscriptionStatus $status,
        ?CarbonImmutable $expiresAt,
        array $limitHits,
    ): SubscriptionAccessMode {
        $mode = match ($status) {
            SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIALING, null => SubscriptionAccessMode::ACTIVE,
            SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED => $this->isInsideGracePeriod($expiresAt)
                ? SubscriptionAccessMode::GRACE_READ_ONLY
                : SubscriptionAccessMode::POST_GRACE_READ_ONLY,
            SubscriptionStatus::SUSPENDED => SubscriptionAccessMode::SUSPENDED,
        };

        if ($mode === SubscriptionAccessMode::ACTIVE && $limitHits !== []) {
            return SubscriptionAccessMode::LIMIT_BLOCKED;
        }

        return $mode;
    }

    private function isInsideGracePeriod(?CarbonImmutable $expiresAt): bool
    {
        if ($expiresAt === null) {
            return false;
        }

        return now()->lessThanOrEqualTo(
            $expiresAt->addDays((int) config('tenanto.subscription.grace_period_days', 7)),
        );
    }

    private function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function remainingCount(?int $limit, int $used): int
    {
        if ($limit === null) {
            return 0;
        }

        return max($limit - $used, 0);
    }

    /**
     * @return array{
     *     mode: string,
     *     limit_hits: list<string>,
     *     grace_ends_at: string|null,
     *     usage: array{properties: int, tenants: int, meters: int, invoices: int},
     *     limits: array{properties: int|null, tenants: int|null, meters: int|null, invoices: int|null}
     * }
     */
    private function defaultSnapshot(): array
    {
        return [
            'mode' => SubscriptionAccessMode::ACTIVE->value,
            'limit_hits' => [],
            'grace_ends_at' => null,
            'usage' => [
                'properties' => 0,
                'tenants' => 0,
                'meters' => 0,
                'invoices' => 0,
            ],
            'limits' => [
                'properties' => null,
                'tenants' => null,
                'meters' => null,
                'invoices' => null,
            ],
        ];
    }
}
