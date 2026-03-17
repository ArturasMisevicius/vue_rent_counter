<?php

namespace App\Support\Shell\Search\Providers;

use App\Models\User;
use App\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Support\Shell\Search\Data\GlobalSearchResultData;
use Illuminate\Support\Facades\Route;

class UserSearchProvider implements GlobalSearchProvider
{
    public function key(): string
    {
        return 'users';
    }

    /**
     * @return list<GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        $routeName = (string) config('tenanto.routes.search.users.view');

        if (! Route::has($routeName)) {
            return [];
        }

        return User::query()
            ->select(['id', 'name', 'email', 'organization_id', 'role'])
            ->when(! $user->isSuperadmin(), fn ($builder) => $builder->where('organization_id', $user->organization_id))
            ->where(function ($builder) use ($query): void {
                $builder
                    ->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%');
            })
            ->limit(5)
            ->get()
            ->map(fn (User $searchResultUser): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->key(),
                label: $searchResultUser->name,
                detail: $searchResultUser->email,
                typeLabel: __((string) data_get(config('tenanto.search.labels'), $this->key(), 'shell.search_groups.users')),
                url: route($routeName, $searchResultUser),
            ))
            ->all();
    }
}
