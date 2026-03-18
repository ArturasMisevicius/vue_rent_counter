<?php

declare(strict_types=1);

namespace App\Filament\Support\Shell\Search\Providers;

use App\Filament\Support\Shell\Search\Contracts\GlobalSearchProvider;
use App\Filament\Support\Shell\Search\Data\GlobalSearchResultData;
use App\Filament\Support\Shell\Search\SearchQueryPattern;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

final class TenantSearchProvider implements GlobalSearchProvider
{
    public function group(): string
    {
        return (string) config('tenanto.search.providers.tenants.group', 'tenants');
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

        return User::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->tenants()
            ->when(
                ! $user->isSuperadmin(),
                fn (Builder $builder): Builder => $builder->forOrganization((int) $user->organization_id),
            )
            ->with([
                'organization:id,name',
            ])
            ->where(function (Builder $builder) use ($pattern): void {
                $builder
                    ->where('name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern);
            })
            ->orderedByName()
            ->limit((int) config('tenanto.search.limit', 5))
            ->get()
            ->map(fn (User $tenant): GlobalSearchResultData => new GlobalSearchResultData(
                group: $this->group(),
                title: $tenant->name,
                subtitle: $this->subtitleFor($user, $tenant),
                url: $this->urlFor($user, $tenant),
            ))
            ->filter(fn (GlobalSearchResultData $result): bool => filled($result->url))
            ->values()
            ->all();
    }

    protected function subtitleFor(User $user, User $tenant): string
    {
        $subtitle = $tenant->email;

        if ($user->isSuperadmin() && $tenant->organization?->name) {
            return trim($subtitle.' · '.$tenant->organization->name, ' ·');
        }

        return $subtitle;
    }

    protected function urlFor(User $user, User $tenant): ?string
    {
        if ($user->isSuperadmin()) {
            $routeName = (string) config('tenanto.search.providers.tenants.superadmin_route', 'filament.admin.resources.organizations.view');

            return Route::has($routeName)
                ? route($routeName, $tenant->organization_id)
                : null;
        }

        $routeName = (string) config('tenanto.search.providers.tenants.route', 'filament.admin.resources.tenants.view');

        return Route::has($routeName)
            ? route($routeName, $tenant)
            : null;
    }
}
