<?php

namespace App\Support\Shell\Search\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Support\Shell\Search\Data\GlobalSearchResultData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class OrganizationSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.organizations.group', 'platform');
    }

    /**
     * @return array<int, GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        $routeName = (string) config('tenanto.search.providers.organizations.route', 'filament.admin.resources.organizations.view');

        if (! $user->isSuperadmin() || ! Route::has($routeName)) {
            return [];
        }

        return Organization::query()
            ->select(['id', 'name', 'slug'])
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('name', 'like', '%'.$query.'%')
                    ->orWhere('slug', 'like', '%'.$query.'%');
            })
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (Organization $organization): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $organization->name,
                subtitle: $organization->slug,
                url: route($routeName, $organization),
            ))
            ->all();
    }
}
