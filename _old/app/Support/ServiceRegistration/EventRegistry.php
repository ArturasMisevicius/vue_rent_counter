<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;

/**
 * Event Registry for organized event and listener registration
 * 
 * Centralizes event handling, rate limiting, and view composer
 * registration following Laravel 12 patterns.
 */
final readonly class EventRegistry
{
    /**
     * Register security event listeners
     */
    public function registerSecurityEvents(): void
    {
        Event::listen(
            \App\Events\SecurityViolationDetected::class,
            \App\Listeners\LogSecurityViolation::class
        );
    }

    /**
     * Register authentication events
     */
    public function registerAuthenticationEvents(): void
    {
        // Set tenant_id in session when user authenticates
        Event::listen(Authenticated::class, function (Authenticated $event) {
            if ($event->user && $event->user->tenant_id) {
                session(['tenant_id' => $event->user->tenant_id]);
            }
        });
    }

    /**
     * Register view composers
     */
    public function registerViewComposers(): void
    {
        View::composer(
            'layouts.app',
            \App\View\Composers\NavigationComposer::class
        );

        // REMOVED: View composer for language-switcher
        // Reason: LanguageSwitcher is a class-based component that provides its own data
        // View composers should only be used with traditional Blade views, not class-based components
        // The composer was causing "Using $this when not in object context" errors
    }

    /**
     * Register rate limiters
     */
    public function registerRateLimiters(): void
    {
        // Rate limiting for admin routes (120 requests per minute per user)
        // Prevents brute force attacks and DoS attempts
        RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.'
                    ], 429);
                });
        });

        // Rate limiting for API routes (60 requests per minute per user)
        RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.'
                    ], 429);
                });
        });
    }

    /**
     * Register collection macros
     */
    public function registerCollectionMacros(): void
    {
        if (! Collection::hasMacro('takeLast')) {
            Collection::macro('takeLast', function (int $count) {
                return $count <= 0
                    ? $this->take(0)
                    : $this->take(-$count);
            });
        }
    }
}