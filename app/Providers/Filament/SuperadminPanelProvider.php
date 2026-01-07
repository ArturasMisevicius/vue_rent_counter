<?php

namespace App\Providers\Filament;

use App\Enums\UserRole;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
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
use Illuminate\Support\Facades\Cache;
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
 * 
 * @package App\Providers\Filament
 */
class SuperadminPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament superadmin panel with system-wide access.
     * 
     * @param Panel $panel The Filament panel instance to configure
     * @return Panel The fully configured panel instance
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
            ])
            ->discoverResources(in: app_path('Filament/Superadmin/Resources'), for: 'App\\Filament\\Superadmin\\Resources')
            ->discoverPages(in: app_path('Filament/Superadmin/Pages'), for: 'App\\Filament\\Superadmin\\Pages')
            ->pages([
                \App\Filament\Superadmin\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Superadmin/Widgets'), for: 'App\\Filament\\Superadmin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Superadmin\Widgets\SystemOverviewWidget::class,
                \App\Filament\Superadmin\Widgets\RecentUsersWidget::class,
            ])
            ->middleware([
                // Core Laravel middleware (session, CSRF, routing)
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                
                // Filament-specific middleware
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Temporarily disable Shield to fix 500 error
            // ->plugins([
            //     FilamentShieldPlugin::make(),
            // ])
            ->globalSearch(false) // Disable global search to reduce load
            ->authMiddleware([
                Authenticate::class,
                // Superadmin security middleware (only applied after authentication)
                \App\Http\Middleware\EnsureUserIsSuperadmin::class,
            ])
            // Simplify navigation groups to reduce rendering load
            ->navigationGroups([
                NavigationGroup::make('System')
                    ->collapsed(false),
                NavigationGroup::make('Users')
                    ->collapsed(false),
                NavigationGroup::make('Monitoring')
                    ->collapsed(true),
            ])
            // Add performance optimizations
            ->spa(false) // Disable SPA mode to reduce complexity
            ->unsavedChangesAlerts(false); // Disable unsaved changes alerts
    }

    /**
     * Bootstrap application services for the superadmin panel.
     * 
     * @return void
     */
    public function boot(): void
    {
        // Configure superadmin-specific navigation visibility with caching
        \Filament\Facades\Filament::serving(function () {
            $user = auth()->user();
            
            if (!$user || $user->role !== UserRole::SUPERADMIN) {
                return;
            }
            
            // Cache navigation state to prevent repeated queries
            $cacheKey = "superadmin.navigation.{$user->id}";
            
            Cache::remember($cacheKey, 300, function () {
                // Superadmin sees all navigation items
                return true;
            });
        });
    }
}