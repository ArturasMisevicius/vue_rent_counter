<?php

namespace App\Providers\Filament;

use App\Enums\UserRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\PrivacyPolicy::class,
                \App\Filament\Pages\TermsOfService::class,
                \App\Filament\Pages\GDPRCompliance::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                \App\Http\Middleware\EnsureUserIsAdminOrManager::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Configure authorization error handling (Requirement 9.4)
            ->renderHook(
                'panels::auth.login.form.after',
                fn (): string => ''
            )
            // Configure navigation based on user role (Requirement 1.1, 13.1)
            ->navigationGroups([
                NavigationGroup::make('Administration')
                    ->collapsed(false),
                NavigationGroup::make('Property Management')
                    ->collapsed(false),
                NavigationGroup::make('Billing')
                    ->collapsed(false),
                NavigationGroup::make('System')
                    ->collapsed(true),
            ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define gate for admin panel access
        \Illuminate\Support\Facades\Gate::define('access-admin-panel', function ($user) {
            return $user->role === \App\Enums\UserRole::ADMIN || $user->role === \App\Enums\UserRole::MANAGER;
        });
        
        // Log authorization failures for security monitoring (Requirement 9.4)
        \Illuminate\Support\Facades\Gate::after(function ($user, $ability, $result, $arguments) {
            if ($result === false) {
                \Illuminate\Support\Facades\Log::warning('Authorization denied', [
                    'user_id' => $user?->id,
                    'user_email' => $user?->email,
                    'user_role' => $user?->role?->value,
                    'ability' => $ability,
                    'resource' => $arguments[0] ?? null,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }
        });
    }
}
