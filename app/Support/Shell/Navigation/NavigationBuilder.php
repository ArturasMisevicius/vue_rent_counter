<?php

namespace App\Support\Shell\Navigation;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NavigationBuilder
{
    /**
     * @return array<int, NavigationGroupData>
     */
    public function adminLike(User $user, Request $request): array
    {
        $groups = [];

        if ($user->isSuperadmin()) {
            $groups[] = $this->group('platform', __('shell.navigation.groups.platform'), [
                $this->item($request, 'filament.admin.pages.platform-dashboard', __('dashboard.title')),
                $this->item($request, 'filament.admin.resources.organizations.index', __('shell.navigation.items.organizations')),
            ]);
        }

        if ($user->isAdmin() || $user->isManager()) {
            $groups[] = $this->group('organization', __('shell.navigation.groups.organization'), [
                $this->item($request, 'filament.admin.pages.organization-dashboard', __('dashboard.title')),
            ]);
        }

        $groups[] = $this->group('account', __('shell.navigation.groups.account'), [
            $this->item($request, 'profile.edit', __('shell.navigation.items.profile')),
        ]);

        return array_values(array_filter($groups));
    }

    /**
     * @return array<int, NavigationItemData>
     */
    public function tenant(Request $request): array
    {
        return array_values(array_filter([
            $this->item($request, 'tenant.home', __('tenant.navigation.home')),
            $this->item($request, 'tenant.readings.create', __('tenant.navigation.readings')),
            $this->item($request, 'tenant.invoices.index', __('tenant.navigation.invoices')),
            $this->item($request, 'tenant.profile.edit', __('tenant.navigation.profile')),
        ]));
    }

    /**
     * @param  array<int, NavigationItemData|null>  $items
     */
    protected function group(string $key, string $label, array $items): ?NavigationGroupData
    {
        $items = array_values(array_filter($items));

        if ($items === []) {
            return null;
        }

        return new NavigationGroupData(
            key: $key,
            label: $label,
            items: $items,
        );
    }

    protected function item(Request $request, string $routeName, string $label): ?NavigationItemData
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return new NavigationItemData(
            label: $label,
            url: route($routeName),
            routeName: $routeName,
            active: $request->routeIs($routeName),
        );
    }
}
