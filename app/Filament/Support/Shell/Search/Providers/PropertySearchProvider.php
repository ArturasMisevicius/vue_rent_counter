<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class PropertySearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.properties.group', 'properties');
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

        return Property::query()
            ->select([
                'id',
                'organization_id',
                'building_id',
                'name',
                'unit_number',
                'type',
            ])
            ->when(
                ! $user->isSuperadmin(),
                fn (Builder $builder): Builder => $builder->forOrganization((int) $user->organization_id),
                fn (Builder $builder): Builder => $builder,
            )
            ->with([
                'building:id,organization_id,name,address_line_1,city',
                'organization:id,name',
            ])
            ->where(function (Builder $builder) use ($pattern): void {
                $builder
                    ->where('name', 'like', $pattern)
                    ->orWhere('unit_number', 'like', $pattern)
                    ->orWhereHas('building', function (Builder $buildingQuery) use ($pattern): void {
                        $buildingQuery
                            ->where('name', 'like', $pattern)
                            ->orWhere('address_line_1', 'like', $pattern);
                    });
            })
            ->ordered()
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (Property $property): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $property->displayName(),
                subtitle: $this->subtitleFor($user, $property),
                url: $this->urlFor($user, $property),
            ))
            ->filter(fn (GlobalSearchResultData $result): bool => filled($result->url))
            ->values()
            ->all();
    }

    protected function subtitleFor(User $user, Property $property): string
    {
        $subtitle = $property->address;

        if ($user->isSuperadmin() && $property->organization?->name) {
            return trim($subtitle.' · '.$property->organization->name, ' ·');
        }

        return $subtitle;
    }

    protected function urlFor(User $user, Property $property): ?string
    {
        if ($user->isSuperadmin()) {
            $routeName = (string) config('tenanto.search.providers.properties.superadmin_route', 'filament.admin.resources.properties.view');

            if (! Route::has($routeName)) {
                return null;
            }

            return $routeName === 'filament.admin.resources.organizations.view'
                ? route($routeName, $property->organization_id)
                : route($routeName, $property);
        }

        $routeName = (string) config('tenanto.search.providers.properties.route', 'filament.admin.resources.properties.view');

        return Route::has($routeName)
            ? route($routeName, $property)
            : null;
    }
}
