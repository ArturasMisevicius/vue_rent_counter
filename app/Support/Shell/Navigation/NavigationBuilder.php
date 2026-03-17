<?php

namespace App\Support\Shell\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class NavigationBuilder
{
    /**
     * @return list<NavigationGroupData>
     */
    public function sidebarGroupsFor(?User $user): array
    {
        if (! $user?->isAdminLike()) {
            return [];
        }

        $definitions = $user->isSuperadmin()
            ? [
                __('shell.sections.platform') => [
                    __('shell.dashboard') => 'filament.admin.pages.platform-dashboard',
                    'Organizations' => 'filament.admin.resources.organizations.index',
                    'Users' => 'filament.admin.resources.users.index',
                ],
                __('shell.sections.account') => [
                    __('shell.profile') => 'profile.edit',
                ],
            ]
            : [
                __('shell.sections.workspace') => [
                    __('shell.dashboard') => 'filament.admin.pages.organization-dashboard',
                    'Buildings' => 'filament.admin.resources.buildings.index',
                    'Properties' => 'filament.admin.resources.properties.index',
                    'Tenants' => 'filament.admin.resources.tenants.index',
                    'Meters' => 'filament.admin.resources.meters.index',
                    'Invoices' => 'filament.admin.resources.invoices.index',
                ],
                __('shell.sections.account') => [
                    __('shell.profile') => 'profile.edit',
                ],
            ];

        return collect($definitions)
            ->map(function (array $items, string $label): ?NavigationGroupData {
                $resolvedItems = collect($items)
                    ->map(fn (string $routeName, string $itemLabel): ?NavigationItemData => $this->makeItem(
                        label: $itemLabel,
                        routeName: $routeName,
                    ))
                    ->filter()
                    ->values()
                    ->all();

                if ($resolvedItems === []) {
                    return null;
                }

                return new NavigationGroupData($label, $resolvedItems);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<NavigationItemData>
     */
    public function tenantItemsFor(?User $user): array
    {
        if (! $user?->isTenant()) {
            return [];
        }

        return collect([
            __('shell.home') => 'tenant.home',
            __('shell.readings') => 'tenant.readings.index',
            __('shell.invoices') => 'tenant.invoices.index',
            __('shell.profile') => 'profile.edit',
        ])
            ->map(fn (string $routeName, string $label): ?NavigationItemData => $this->makeItem(
                label: $label,
                routeName: $routeName,
            ))
            ->filter()
            ->values()
            ->all();
    }

    private function makeItem(string $label, string $routeName): ?NavigationItemData
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return new NavigationItemData(
            label: $label,
            routeName: $routeName,
            url: route($routeName),
            isActive: request()->routeIs($routeName),
        );
    }
}
