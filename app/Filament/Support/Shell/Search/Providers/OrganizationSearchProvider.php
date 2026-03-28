<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class OrganizationSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.organizations.group', 'organizations');
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

        $pattern = SearchQueryPattern::from($query)->likePattern();

        return Organization::query()
            ->select(['id', 'name'])
            ->where('name', 'like', $pattern)
            ->orderBy('name')
            ->orderBy('id')
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (Organization $organization): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $organization->name,
                subtitle: null,
                url: route($routeName, $organization),
            ))
            ->all();
    }
}
