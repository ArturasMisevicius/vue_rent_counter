<?php

namespace App\Providers\Filament;

use App\Filament\Support\Shell\Navigation\NavigationBuilder as ShellNavigationBuilder;
use App\Filament\Support\Workspace\WorkspaceContext;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureAccountIsAccessible;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Livewire\Shell\Sidebar;
use App\Livewire\Shell\Topbar;
use App\Models\User;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('app')
            ->favicon('/favicon')
            ->login(fn () => redirect()->route('login'))
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->viteTheme(['resources/css/app.css', 'resources/js/app.js'])
            ->maxContentWidth(Width::Full)
            ->simplePageMaxContentWidth(Width::Full)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->topbarLivewireComponent(Topbar::class)
            ->sidebarLivewireComponent(Sidebar::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('components.shell.session-expiry-monitor'))
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $this->buildNavigation($builder))
            ->navigationGroups([
                NavigationGroup::make(__('shell.navigation.groups.platform')),
                NavigationGroup::make(__('shell.navigation.groups.properties')),
                NavigationGroup::make(__('shell.navigation.groups.billing')),
                NavigationGroup::make(__('shell.navigation.groups.reports')),
                NavigationGroup::make(__('shell.navigation.groups.my_home')),
                NavigationGroup::make(__('shell.navigation.groups.account')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                SetAuthenticatedUserLocale::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                SecurityHeaders::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateAdminPanel::class,
                EnsureAccountIsAccessible::class,
                EnsureOnboardingIsComplete::class,
                CheckSubscriptionStatus::class,
            ]);
    }

    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $builder->groups([]);
        }

        $groups = app(ShellNavigationBuilder::class)->forUser($user, request());

        return $builder->groups(array_map(
            fn ($group): NavigationGroup => $this->panelNavigationGroup($group->label, $group->items),
            $groups,
        ));
    }

    /**
     * @param  array<int, mixed>  $items
     */
    protected function panelNavigationGroup(string $label, array $items): NavigationGroup
    {
        return NavigationGroup::make($label)->items(array_map(
            fn ($item): NavigationItem => $this->panelNavigationItem(
                $item->label,
                $item->url,
                $item->routeName,
                $item->active,
            ),
            $items,
        ));
    }

    protected function panelNavigationItem(string $label, string $url, string $routeName, bool $active): NavigationItem
    {
        return NavigationItem::make($label)
            ->url($url)
            ->isActiveWhen(fn (): bool => $active)
            ->visible(true)
            ->extraAttributes([
                'data-shell-route' => $routeName,
            ]);
    }

    protected function currentWorkspace(): ?WorkspaceContext
    {
        return app(WorkspaceResolver::class)->current();
    }
}
