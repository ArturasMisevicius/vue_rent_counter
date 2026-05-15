<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\Building;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class BuildingSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.buildings.group', 'buildings');
    }

    /**
     * @return array<int, GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        if ((! $user->isSuperadmin() && ! $user->isAdmin() && ! $user->isManager()) || blank($query)) {
            return [];
        }

        $pattern = SearchQueryPattern::from($query)->likePattern();

        return Building::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'address_line_1',
                'city',
            ])
            ->when(
                ! $user->isSuperadmin(),
                fn (Builder $builder): Builder => $builder->forOrganization((int) $user->organization_id),
                fn (Builder $builder): Builder => $builder,
            )
            ->with([
                'organization:id,name',
            ])
            ->where(function (Builder $builder) use ($pattern): void {
                $builder
                    ->where('name', 'like', $pattern)
                    ->orWhere('address_line_1', 'like', $pattern)
                    ->orWhere('city', 'like', $pattern);
            })
            ->ordered()
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (Building $building): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $building->displayName(),
                subtitle: $this->subtitleFor($user, $building),
                url: $this->urlFor($user, $building),
            ))
            ->filter(fn (GlobalSearchResultData $result): bool => filled($result->url))
            ->values()
            ->all();
    }

    protected function subtitleFor(User $user, Building $building): string
    {
        $subtitle = collect([
            $building->address_line_1,
            $building->city,
        ])->filter()->implode(', ');

        if ($user->isSuperadmin() && $building->organization?->name) {
            return trim($subtitle.' · '.$building->organization->name, ' ·');
        }

        return $subtitle;
    }

    protected function urlFor(User $user, Building $building): ?string
    {
        if ($user->isSuperadmin()) {
            $routeName = (string) config('tenanto.search.providers.buildings.superadmin_route', 'filament.admin.resources.buildings.view');

            if (! Route::has($routeName)) {
                return null;
            }

            return $routeName === 'filament.admin.resources.organizations.view'
                ? route($routeName, $building->organization_id)
                : route($routeName, $building);
        }

        $routeName = (string) config('tenanto.search.providers.buildings.route', 'filament.admin.resources.buildings.view');

        return Route::has($routeName)
            ? route($routeName, $building)
            : null;
    }
}
