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

        foreach ($this->groupsFor($user) as $groupKey => $items) {
            $groups[] = $this->configuredGroup($request, $groupKey, $items);
        }

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

    /**
     * @param  array<int, array{label: string, route: string}>  $items
     */
    protected function configuredGroup(Request $request, string $groupKey, array $items): ?NavigationGroupData
    {
        return $this->group(
            $groupKey,
            __("shell.navigation.groups.{$groupKey}"),
            array_map(
                fn (array $item): ?NavigationItemData => $this->item(
                    $request,
                    $item['route'],
                    __($item['label']),
                ),
                $items,
            ),
        );
    }

    /**
     * @return array<string, array<int, array{label: string, route: string}>>
     */
    protected function groupsFor(User $user): array
    {
        $role = match (true) {
            $user->isSuperadmin() => 'superadmin',
            $user->isAdmin() => 'admin',
            $user->isManager() => 'manager',
            default => null,
        };

        if ($role === null) {
            return [];
        }

        /** @var array<string, array<int, array{label: string, route: string}>> $groups */
        $groups = config("tenanto.shell.navigation.roles.{$role}", []);

        return $groups;
    }
}
