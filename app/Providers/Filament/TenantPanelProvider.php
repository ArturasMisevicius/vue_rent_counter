<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Pages\Profile;
use App\Filament\Tenant\Resources\InvoiceResource;
use App\Filament\Tenant\Resources\MeterReadingResource;
use App\Filament\Tenant\Resources\PropertyResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Filament Tenant Panel Provider
 *
 * Configures the Filament v4 tenant panel with property-scoped access
 * for the Vilnius Utilities Billing System.
 *
 * ## Security Architecture
 *
 * The tenant panel implements property-scoped security:
 *
 * 1. **Role-Based Access** (EnsureUserIsTenant)
 *    - Restricts panel access to TENANT role only
 *    - Blocks all other roles from accessing tenant panel
 *
 * 2. **Property Isolation**
 *    - Tenants only see their assigned property data
 *    - Automatic scoping to property_id
 *    - Read-only access to most data
 *
 * ## Navigation Configuration
 *
 * Navigation groups are organized by tenant needs:
 * - My Property: Property details, meters, consumption
 * - Billing: Invoices, payments, history
 * - Account: Profile, preferences
 */
final class TenantPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament tenant panel with property-scoped access.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('tenant')
            ->login()
            ->authGuard('web')
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->viteTheme('resources/css/filament/theme.css')
            ->brandName('Tenant Portal')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('app.nav_groups.my_property'))
                    ->icon('heroicon-o-home')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label(__('app.nav_groups.billing'))
                    ->icon('heroicon-o-document-text')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label(__('app.nav_groups.account'))
                    ->icon('heroicon-o-user')
                    ->collapsed(true),
            ])
            ->pages([
                Dashboard::class,
                Profile::class,
            ])
            ->resources([
                PropertyResource::class,
                MeterReadingResource::class,
                InvoiceResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureUserIsTenant::class,
            ])
            ->globalSearch(false) // Tenants don't need global search
            ->spa(false)
            ->unsavedChangesAlerts(false) // Tenants mostly view data
            ->sidebarCollapsibleOnDesktop(true)
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s'); // Less frequent polling
    }

    /**
     * Bootstrap application services for the tenant panel.
     */
    public function boot(): void
    {
        // Panel-specific boot logic can be added here if needed
    }
}
