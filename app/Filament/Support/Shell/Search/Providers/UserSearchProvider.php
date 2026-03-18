<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class UserSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.users.group', 'organization');
    }

    /**
     * @return array<int, GlobalSearchResultData>
     */
    public function search(User $user, string $query): array
    {
        $routeName = (string) config('tenanto.search.providers.users.route', 'filament.admin.resources.users.view');

        if (! $user->isAdminLike() || $user->isTenant() || blank($user->organization_id) || ! Route::has($routeName)) {
            return [];
        }

        $pattern = SearchQueryPattern::from($query)->likePattern();

        return User::query()
            ->select(['id', 'name', 'email', 'organization_id'])
            ->where('organization_id', $user->organization_id)
            ->where(function (Builder $builder) use ($pattern): void {
                $builder
                    ->where('name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern);
            })
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (User $searchResult): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $searchResult->name,
                subtitle: $searchResult->email,
                url: route($routeName, $searchResult),
            ))
            ->all();
    }
}
