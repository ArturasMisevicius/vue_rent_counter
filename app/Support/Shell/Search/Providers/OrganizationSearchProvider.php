<?php

namespace App\Support\Shell\Search\Providers;

use App\Models\Organization;
use App\Models\User;
use App\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Support\Shell\Search\Data\GlobalSearchResultData;
use Illuminate\Support\Facades\Route;

class OrganizationSearchProvider implements GlobalSearchProvider
{
    public function key(): string
    {
        return 'organizations';
    }

    /**
     * @return list<GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        $routeName = (string) config('tenanto.routes.search.organizations.view');

        if ((! $user->isSuperadmin()) || (! Route::has($routeName))) {
            return [];
        }

        return Organization::query()
            ->select(['id', 'name', 'slug', 'owner_user_id'])
            ->with(['owner:id,email'])
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('name', 'like', '%'.$query.'%')
                    ->orWhere('slug', 'like', '%'.$query.'%');
            })
            ->limit(5)
            ->get()
            ->map(fn (Organization $organization): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->key(),
                label: $organization->name,
                detail: $organization->owner?->email ?? $organization->slug,
                typeLabel: __((string) data_get(config('tenanto.search.labels'), $this->key(), 'shell.search_groups.organizations')),
                url: route($routeName, $organization),
            ))
            ->all();
    }
}
