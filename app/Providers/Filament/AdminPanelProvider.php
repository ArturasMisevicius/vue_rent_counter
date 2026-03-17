<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Profile;
use App\Filament\Pages\Settings;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Http\Controllers\Filament\RedirectToPublicLoginController;
use App\Http\Middleware\EnsureAccountIsAccessible;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Livewire\Shell\Sidebar;
use App\Livewire\Shell\Topbar;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(RedirectToPublicLoginController::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topbarLivewireComponent(Topbar::class)
            ->sidebarLivewireComponent(Sidebar::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                BuildingResource::class,
                PropertyResource::class,
                TenantResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                Profile::class,
                Settings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureAccountIsAccessible::class,
                EnsureOnboardingIsComplete::class,
            ]);
    }
}
