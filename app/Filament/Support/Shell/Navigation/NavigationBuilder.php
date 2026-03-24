<?php

namespace App\Filament\Support\Shell\Navigation;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationBuilder
{
    /**
     * @return array<int, NavigationGroupData>
     */
    public function forUser(User $user, Request $request): array
    {
        $groups = [];

        foreach ($this->groupsFor($user) as $groupKey => $items) {
            $groups[] = $this->configuredGroup($request, $groupKey, $items);
        }

        return array_values(array_filter($groups));
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

    /**
     * @param  list<string>|null  $activePatterns
     */
    protected function item(Request $request, string $routeName, string $label, ?array $activePatterns = null): ?NavigationItemData
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return new NavigationItemData(
            label: $label,
            url: route($routeName),
            routeName: $routeName,
            active: $request->routeIs(...($activePatterns ?? [$routeName])),
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
                    $this->resolveLabel($item['label']),
                    $this->activePatternsFor($item),
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
        $role = $this->configurationRoleFor($user);

        if ($role === null) {
            return [];
        }

        /** @var array<string, array<int, array{label: string, route: string}>> $groups */
        $groups = config("tenanto.shell.navigation.roles.{$role}", []);

        return $groups;
    }

    protected function configurationRoleFor(User $user): ?string
    {
        return match (true) {
            $user->isSuperadmin() => 'superadmin',
            $user->isAdmin(), $user->isManager() => 'admin',
            $user->isTenant() => 'tenant',
            default => null,
        };
    }

    /**
     * @param  array{route: string, label: string, active_patterns?: list<string>}  $item
     * @return list<string>
     */
    protected function activePatternsFor(array $item): array
    {
        if (array_key_exists('active_patterns', $item)) {
            return $item['active_patterns'];
        }

        $routeName = $item['route'];

        if (str_contains($routeName, '.resources.') && str_ends_with($routeName, '.index')) {
            return [Str::replaceLast('.index', '.*', $routeName)];
        }

        return [$routeName];
    }

    protected function resolveLabel(string $label): string
    {
        if (! str_contains($label, '.')) {
            return $label;
        }

        $translated = __($label);

        return is_string($translated) ? $translated : $label;
    }
}
