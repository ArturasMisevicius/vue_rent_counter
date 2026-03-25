<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class MeterReadingSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.readings.group', 'readings');
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

        $queryBuilder = MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
            ])
            ->with([
                'meter:id,organization_id,property_id,name,identifier,unit',
                'organization:id,name',
            ])
            ->whereHas('meter', function (Builder $meterQuery) use ($pattern): void {
                $meterQuery
                    ->where('name', 'like', $pattern)
                    ->orWhere('identifier', 'like', $pattern);
            })
            ->latestFirst()
            ->limit((int) config('tenanto.search.limit', 5))
            ->when(
                $user->isTenant(),
                fn (Builder $builder): Builder => $builder->when(
                    $hasTenantWorkspace,
                    fn (Builder $tenantBuilder): Builder => $tenantBuilder
                        ->forOrganization((int) $user->organization_id)
                        ->forProperty($tenantPropertyId)
                        ->submittedBy($user->id),
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
            ->map(fn (MeterReading $reading): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: (string) ($reading->meter?->name ?? $reading->meter?->identifier ?? __('admin.meter_readings.singular')),
                subtitle: $this->subtitleFor($user, $reading),
                url: $this->urlFor($user, $reading),
            ))
            ->filter(fn (GlobalSearchResultData $result): bool => filled($result->url))
            ->values()
            ->all();
    }

    protected function subtitleFor(User $user, MeterReading $reading): string
    {
        $summary = trim(implode(' · ', array_filter([
            $reading->meter?->identifier,
            $reading->reading_date?->locale(app()->getLocale())->isoFormat('ll'),
            $this->formatDecimal((float) $reading->reading_value, 3)
                .' '
                .($reading->meter?->unit ?? ''),
        ])));

        if ($user->isSuperadmin() && $reading->organization?->name) {
            return trim($reading->organization->name.' · '.$summary, ' ·');
        }

        return $summary;
    }

    protected function urlFor(User $user, MeterReading $reading): ?string
    {
        if ($user->isTenant()) {
            $routeName = (string) config('tenanto.search.providers.readings.tenant_route', 'filament.admin.pages.tenant-dashboard');

            return Route::has($routeName)
                ? route($routeName).'#tenant-reading-'.$reading->id
                : null;
        }

        if ($user->isSuperadmin()) {
            $routeName = (string) config('tenanto.search.providers.readings.superadmin_route', 'filament.admin.resources.meter-readings.view');

            if (! Route::has($routeName)) {
                return null;
            }

            return $routeName === 'filament.admin.resources.organizations.view'
                ? route($routeName, $reading->organization_id)
                : route($routeName, $reading);
        }

        $routeName = (string) config('tenanto.search.providers.readings.route', 'filament.admin.resources.meter-readings.view');

        return Route::has($routeName)
            ? route($routeName, $reading)
            : null;
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
