<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class InvoiceSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.invoices.group', 'invoices');
    }

    /**
     * @return array<int, GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        if (blank($query)) {
            return [];
        }

        $pattern = SearchQueryPattern::from($query)->likePattern();
        $tenantPropertyId = (int) ($user->currentPropertyAssignment?->property_id ?? 0);
        $hasTenantWorkspace = $tenantPropertyId > 0 && $user->organization_id !== null;
        $isOrganizationUser = $user->isAdmin() || $user->isManager();

        $queryBuilder = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'due_date',
                'document_path',
            ])
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name,address_line_1,city',
                'tenant:id,organization_id,name,email',
                'organization:id,name',
            ])
            ->where('invoice_number', 'like', $pattern)
            ->latestBillingFirst()
            ->limit((int) config('tenanto.search.limit', 5))
            ->when(
                $user->isTenant(),
                fn (Builder $builder): Builder => $builder->when(
                    $hasTenantWorkspace,
                    fn (Builder $tenantBuilder): Builder => $tenantBuilder
                        ->forOrganization((int) $user->organization_id)
                        ->forTenant($user->id)
                        ->forProperty($tenantPropertyId),
                    fn (Builder $tenantBuilder): Builder => $tenantBuilder->whereKey(-1),
                ),
                fn (Builder $builder): Builder => $builder->when(
                    $isOrganizationUser,
                    fn (Builder $organizationBuilder): Builder => $organizationBuilder
                        ->forOrganization((int) $user->organization_id),
                    fn (Builder $organizationBuilder): Builder => $organizationBuilder->when(
                        $user->isSuperadmin(),
                        fn (Builder $superadminBuilder): Builder => $superadminBuilder,
                        fn (Builder $deniedBuilder): Builder => $deniedBuilder->whereKey(-1),
                    ),
                ),
            );

        return $queryBuilder
            ->get()
            ->map(fn (Invoice $invoice): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $invoice->invoice_number,
                subtitle: $this->subtitleFor($user, $invoice),
                url: $this->urlFor($user, $invoice),
            ))
            ->filter(fn (GlobalSearchResultData $result): bool => filled($result->url))
            ->values()
            ->all();
    }

    protected function subtitleFor(User $user, Invoice $invoice): string
    {
        $period = implode(' → ', array_filter([
            $invoice->billing_period_start?->locale(app()->getLocale())->isoFormat('ll'),
            $invoice->billing_period_end?->locale(app()->getLocale())->isoFormat('ll'),
        ]));

        if ($user->isTenant()) {
            return $period;
        }

        $tenantName = $invoice->tenant?->name;

        if ($user->isSuperadmin() && $invoice->organization?->name) {
            return trim(implode(' · ', array_filter([$invoice->organization->name, $tenantName, $period])), ' ·');
        }

        return trim(implode(' · ', array_filter([$tenantName, $period])), ' ·');
    }

    protected function urlFor(User $user, Invoice $invoice): ?string
    {
        if ($user->isTenant()) {
            $routeName = (string) config('tenanto.search.providers.invoices.tenant_route', 'filament.admin.pages.tenant-invoice-history');

            return Route::has($routeName)
                ? route($routeName).'#tenant-invoice-'.$invoice->id
                : null;
        }

        if ($user->isSuperadmin()) {
            $routeName = (string) config('tenanto.search.providers.invoices.superadmin_route', 'filament.admin.resources.invoices.view');

            if (! Route::has($routeName)) {
                return null;
            }

            return $routeName === 'filament.admin.resources.organizations.view'
                ? route($routeName, $invoice->organization_id)
                : route($routeName, $invoice);
        }

        $routeName = (string) config('tenanto.search.providers.invoices.route', 'filament.admin.resources.invoices.view');

        return Route::has($routeName)
            ? route($routeName, $invoice)
            : null;
    }
}
