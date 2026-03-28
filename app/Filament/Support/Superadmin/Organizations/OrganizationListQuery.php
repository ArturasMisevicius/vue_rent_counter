<?php

namespace App\Filament\Support\Superadmin\Organizations;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

final class OrganizationListQuery
{
    /**
     * @param  Builder<Organization>|null  $query
     * @return Builder<Organization>
     */
    public function build(?Builder $query = null): Builder
    {
        return ($query ?? Organization::query())->forSuperadminControlPlane();
    }

    /**
     * @param  array<int, string>  $statuses
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterByStatuses(Builder $query, array $statuses): Builder
    {
        $values = $this->filledValues($statuses);

        if ($values === []) {
            return $query;
        }

        return $query->whereIn('status', $values);
    }

    /**
     * @param  array<int, string>  $plans
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterByPlans(Builder $query, array $plans): Builder
    {
        $values = $this->filledValues($plans);

        if ($values === []) {
            return $query;
        }

        return $query->whereHas(
            'currentSubscription',
            fn (Builder $subscriptionQuery): Builder => $subscriptionQuery->whereIn('plan', $values),
        );
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterByCreatedBetween(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $builder): Builder => $builder->whereDate('created_at', '>=', (string) $from),
            )
            ->when(
                filled($to),
                fn (Builder $builder): Builder => $builder->whereDate('created_at', '<=', (string) $to),
            );
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterByTrialExpiryRange(Builder $query, mixed $from, mixed $to): Builder
    {
        if (! filled($from) && ! filled($to)) {
            return $query;
        }

        return $query->whereHas('currentSubscription', function (Builder $subscriptionQuery) use ($from, $to): Builder {
            return $subscriptionQuery
                ->where('is_trial', true)
                ->when(
                    filled($from),
                    fn (Builder $builder): Builder => $builder->whereDate('expires_at', '>=', (string) $from),
                )
                ->when(
                    filled($to),
                    fn (Builder $builder): Builder => $builder->whereDate('expires_at', '<=', (string) $to),
                );
        });
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterByOverdueInvoicePresence(Builder $query, ?bool $hasOverdueInvoices): Builder
    {
        if ($hasOverdueInvoices === null) {
            return $query;
        }

        return $hasOverdueInvoices
            ? $query->whereHas('invoices', fn (Builder $invoiceQuery): Builder => $invoiceQuery->whereOverdueAsOf())
            : $query->whereDoesntHave('invoices', fn (Builder $invoiceQuery): Builder => $invoiceQuery->whereOverdueAsOf());
    }

    /**
     * @param  Builder<Organization>  $query
     * @return Builder<Organization>
     */
    public function filterBySecurityViolationPresence(Builder $query, ?bool $hasSecurityViolations): Builder
    {
        if ($hasSecurityViolations === null) {
            return $query;
        }

        return $hasSecurityViolations
            ? $query->whereHas('securityViolations')
            : $query->whereDoesntHave('securityViolations');
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function filledValues(array $values): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $value): string => (string) $value, $values),
            static fn (string $value): bool => filled($value),
        ));
    }
}
