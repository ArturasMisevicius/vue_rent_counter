<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\PlatformUserResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Superadmin\Pages\Dashboard;
use App\Filament\Superadmin\Widgets\ExpiringSubscriptionsWidget;
use App\Filament\Superadmin\Widgets\RecentUsersWidget;
use App\Filament\Superadmin\Widgets\SystemOverviewWidget;
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
 * Filament Superadmin Panel Provider
 * 
 * Configures the Filament v4 superadmin panel with comprehensive system-wide access
 * and organization management for the Vilnius Utilities Billing System.
 * 
 * ## Security Architecture
 * 
 * The superadmin panel implements a simplified security model:
 * 
 * 1. **Role-Based Access** (EnsureUserIsSuperadmin)
 *    - Restricts panel access to SUPERADMIN role only
 *    - Blocks all other roles from accessing superadmin panel
 * 
 * 2. **System-Wide Access**
 *    - Superadmins bypass tenant isolation
 *    - Full access to all organizations and subscriptions
 *    - System monitoring and management capabilities
 * 
 * ## Navigation Configuration
 * 
 * Navigation groups are organized by system administration areas:
 * - System Management: Organizations, subscriptions, system health
 * - User Management: All users across organizations
 * - Monitoring: Audit logs, performance metrics, backups
 */
final class SuperadminPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament superadmin panel with system-wide access.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->login()
            ->authGuard('web')
            ->colors([
                'primary' => Color::Red,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->brandName('Superadmin Panel')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(__('app.nav_groups.system_management'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label(__('app.nav_groups.user_management'))
                    ->icon('heroicon-o-users')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label(__('app.nav_groups.monitoring'))
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(true),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->resources([
                OrganizationResource::class,
                SubscriptionResource::class,
                PlatformUserResource::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                SystemOverviewWidget::class,
                ExpiringSubscriptionsWidget::class,
                RecentUsersWidget::class,
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
                \App\Http\Middleware\EnsureUserIsSuperadmin::class,
            ])
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa(false)
            ->unsavedChangesAlerts(true)
            ->sidebarCollapsibleOnDesktop(true)
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s');
    }

    /**
     * Bootstrap application services for the superadmin panel.
     */
    public function boot(): void
    {
        // Panel-specific boot logic can be added here if needed
    }
}
