<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\EnsureAccountIsAccessible;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Livewire\Shell\Sidebar;
use App\Livewire\Shell\Topbar;
use BackedEnum;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('app')
            ->login(fn () => redirect()->route('login'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topbarLivewireComponent(Topbar::class)
            ->sidebarLivewireComponent(Sidebar::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                SecurityHeaders::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateAdminPanel::class,
                EnsureAccountIsAccessible::class,
                EnsureOnboardingIsComplete::class,
            ]);
    }

    protected function buildNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->groups([
            NavigationGroup::make(__('shell.navigation.groups.platform'))->items([
                $this->navigationItem(
                    label: __('dashboard.title'),
                    icon: Heroicon::OutlinedSquares2x2,
                    routeName: 'filament.admin.pages.platform-dashboard',
                    activePatterns: [
                        'filament.admin.pages.dashboard',
                        'filament.admin.pages.platform-dashboard',
                    ],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('superadmin.organizations.plural'),
                    icon: Heroicon::OutlinedRectangleStack,
                    routeName: 'filament.admin.resources.organizations.index',
                    activePatterns: ['filament.admin.resources.organizations.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.users'),
                    icon: Heroicon::OutlinedUserGroup,
                    routeName: 'filament.admin.resources.users.index',
                    activePatterns: ['filament.admin.resources.users.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.subscriptions'),
                    icon: Heroicon::OutlinedCreditCard,
                    routeName: 'filament.admin.resources.subscriptions.index',
                    activePatterns: ['filament.admin.resources.subscriptions.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.platform_notifications'),
                    icon: Heroicon::OutlinedBellAlert,
                    routeName: 'filament.admin.resources.platform-notifications.index',
                    activePatterns: ['filament.admin.resources.platform-notifications.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.languages'),
                    icon: Heroicon::OutlinedLanguage,
                    routeName: 'filament.admin.resources.languages.index',
                    activePatterns: ['filament.admin.resources.languages.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.translation_management'),
                    icon: Heroicon::OutlinedLanguage,
                    routeName: 'filament.admin.pages.translation-management',
                    activePatterns: ['filament.admin.pages.translation-management'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.system_configuration'),
                    icon: Heroicon::OutlinedCog6Tooth,
                    routeName: 'filament.admin.pages.system-configuration',
                    activePatterns: ['filament.admin.pages.system-configuration'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
            ]),
            NavigationGroup::make(__('shell.navigation.groups.properties'))->items([
                $this->navigationItem(
                    label: __('dashboard.title'),
                    icon: Heroicon::OutlinedSquares2x2,
                    routeName: 'filament.admin.pages.organization-dashboard',
                    activePatterns: [
                        'filament.admin.pages.dashboard',
                        'filament.admin.pages.organization-dashboard',
                    ],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.buildings.plural'),
                    icon: Heroicon::OutlinedBuildingOffice2,
                    routeName: 'filament.admin.resources.buildings.index',
                    activePatterns: ['filament.admin.resources.buildings.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.properties.plural'),
                    icon: Heroicon::OutlinedHome,
                    routeName: 'filament.admin.resources.properties.index',
                    activePatterns: ['filament.admin.resources.properties.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.tenants.plural'),
                    icon: Heroicon::OutlinedUsers,
                    routeName: 'filament.admin.resources.tenants.index',
                    activePatterns: ['filament.admin.resources.tenants.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.meters.plural'),
                    icon: Heroicon::OutlinedBolt,
                    routeName: 'filament.admin.resources.meters.index',
                    activePatterns: ['filament.admin.resources.meters.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.meter_readings.plural'),
                    icon: Heroicon::OutlinedClipboardDocumentList,
                    routeName: 'filament.admin.resources.meter-readings.index',
                    activePatterns: ['filament.admin.resources.meter-readings.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
            ]),
            NavigationGroup::make(__('shell.navigation.groups.billing'))->items([
                $this->navigationItem(
                    label: __('admin.invoices.plural'),
                    icon: Heroicon::OutlinedDocumentText,
                    routeName: 'filament.admin.resources.invoices.index',
                    activePatterns: ['filament.admin.resources.invoices.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.providers.plural'),
                    icon: Heroicon::OutlinedBuildingOffice2,
                    routeName: 'filament.admin.resources.providers.index',
                    activePatterns: ['filament.admin.resources.providers.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('admin.tariffs.plural'),
                    icon: Heroicon::OutlinedReceiptPercent,
                    routeName: 'filament.admin.resources.tariffs.index',
                    activePatterns: ['filament.admin.resources.tariffs.*'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.subscriptions'),
                    icon: Heroicon::OutlinedCreditCard,
                    routeName: 'filament.admin.resources.subscriptions.index',
                    activePatterns: ['filament.admin.resources.subscriptions.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
            ]),
            NavigationGroup::make(__('shell.navigation.groups.reports'))->items([
                $this->navigationItem(
                    label: __('shell.navigation.items.reports'),
                    icon: Heroicon::OutlinedChartBar,
                    routeName: 'filament.admin.pages.reports',
                    activePatterns: ['filament.admin.pages.reports'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.audit_logs'),
                    icon: Heroicon::OutlinedClipboardDocument,
                    routeName: 'filament.admin.resources.audit-logs.index',
                    activePatterns: ['filament.admin.resources.audit-logs.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.security_violations'),
                    icon: Heroicon::OutlinedShieldExclamation,
                    routeName: 'filament.admin.resources.security-violations.index',
                    activePatterns: ['filament.admin.resources.security-violations.*'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.integration_health'),
                    icon: Heroicon::OutlinedSignal,
                    routeName: 'filament.admin.pages.integration-health',
                    activePatterns: ['filament.admin.pages.integration-health'],
                    visible: fn (): bool => $this->isSuperadmin(),
                ),
            ]),
            NavigationGroup::make(__('shell.navigation.groups.my_home'))->items([
                $this->navigationItem(
                    label: __('tenant.navigation.home'),
                    icon: Heroicon::OutlinedHome,
                    routeName: 'filament.admin.pages.dashboard',
                    activePatterns: ['filament.admin.pages.dashboard', 'tenant.home'],
                    visible: fn (): bool => $this->isTenant(),
                ),
                $this->navigationItem(
                    label: __('tenant.pages.property.title'),
                    icon: Heroicon::OutlinedBuildingOffice2,
                    routeName: 'tenant.property.show',
                    activePatterns: ['tenant.property.*'],
                    visible: fn (): bool => $this->isTenant(),
                ),
                $this->navigationItem(
                    label: __('tenant.navigation.readings'),
                    icon: Heroicon::OutlinedClipboardDocumentList,
                    routeName: 'tenant.readings.create',
                    activePatterns: ['tenant.readings.*'],
                    visible: fn (): bool => $this->isTenant(),
                ),
                $this->navigationItem(
                    label: __('tenant.navigation.invoices'),
                    icon: Heroicon::OutlinedDocumentText,
                    routeName: 'tenant.invoices.index',
                    activePatterns: ['tenant.invoices.*'],
                    visible: fn (): bool => $this->isTenant(),
                ),
            ]),
            NavigationGroup::make(__('shell.navigation.groups.account'))->items([
                $this->navigationItem(
                    label: __('shell.navigation.items.profile'),
                    icon: Heroicon::OutlinedUserCircle,
                    routeName: 'filament.admin.pages.profile',
                    activePatterns: ['filament.admin.pages.profile'],
                    visible: fn (): bool => $this->isAuthenticated(),
                ),
                $this->navigationItem(
                    label: __('shell.navigation.items.settings'),
                    icon: Heroicon::OutlinedCog6Tooth,
                    routeName: 'filament.admin.pages.settings',
                    activePatterns: ['filament.admin.pages.settings'],
                    visible: fn (): bool => $this->isAdminOrManager(),
                ),
            ]),
        ]);
    }

    /**
     * @param  list<string>  $activePatterns
     */
    protected function navigationItem(
        string $label,
        string|BackedEnum|null $icon,
        string $routeName,
        array $activePatterns,
        callable|bool $visible,
    ): NavigationItem {
        return NavigationItem::make($label)
            ->group($label)
            ->icon($icon)
            ->url(fn (): ?string => Route::has($routeName) ? route($routeName) : null)
            ->isActiveWhen(fn (): bool => request()->routeIs(...$activePatterns))
            ->visible(fn (): bool => Route::has($routeName) && (bool) value($visible))
            ->extraAttributes([
                'data-shell-route' => $routeName,
            ]);
    }

    protected function isAuthenticated(): bool
    {
        return auth()->check();
    }

    protected function isSuperadmin(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    protected function isAdminOrManager(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }

    protected function isTenant(): bool
    {
        return auth()->user()?->isTenant() ?? false;
    }
}
