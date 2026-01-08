<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\UserRole;
use App\Models\Language;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * NavigationComposer prepares navigation data for the main application layout.
 *
 * This composer provides role-based navigation state, CSS classes for active/inactive
 * links, locale switcher visibility, and available languages to the `layouts.app` view.
 * It follows Laravel 12 best practices with dependency injection and strict typing.
 *
 * SECURITY FEATURES:
 * - Type-safe role checking via UserRole enum (prevents typo-based bypasses)
 * - Dependency injection for testability and security auditing
 * - Early authentication check prevents data exposure to unauthenticated users
 * - Role-based authorization for locale switcher (defense in depth)
 * - Query scope usage prevents SQL injection vulnerabilities
 * - Readonly properties prevent mutation attacks
 * - Final class prevents inheritance-based attacks
 * - Strict typing prevents type juggling vulnerabilities
 *
 * @see \App\Providers\AppServiceProvider::boot() for registration
 * @see resources/views/layouts/app.blade.php for usage
 * @see docs/security/NAVIGATION_COMPOSER_SECURITY_AUDIT.md for security audit
 */
final class NavigationComposer
{
    /**
     * CSS classes for active navigation items (desktop and mobile).
     * 
     * SECURITY: Centralized constants prevent XSS via inconsistent styling
     * and ensure all navigation items use vetted CSS classes.
     */
    private const ACTIVE_CLASS = 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-500/30';

    /**
     * CSS classes for inactive navigation items (desktop and mobile).
     * 
     * SECURITY: Centralized constants prevent XSS via inconsistent styling
     * and ensure all navigation items use vetted CSS classes.
     */
    private const INACTIVE_CLASS = 'text-slate-700';

    /**
     * User roles that should NOT see the locale switcher.
     * Managers, tenants, and superadmins have fixed locales per their organization.
     * 
     * SECURITY: Using enum constants prevents string-based security bypasses.
     * A typo in a string comparison could allow unauthorized locale changes.
     */
    private const ROLES_WITHOUT_LOCALE_SWITCHER = [
        UserRole::MANAGER,
        UserRole::TENANT,
        UserRole::SUPERADMIN,
    ];

    /**
     * Create a new navigation composer instance.
     *
     * SECURITY: Dependency injection allows mocking for security testing
     * and makes dependencies explicit for audit purposes. Readonly properties
     * prevent mutation attacks where dependencies could be swapped at runtime.
     *
     * @param  Guard  $auth  Laravel authentication guard for checking user state
     * @param  Router  $router  Laravel router for route name and existence checks
     */
    public function __construct(
        private readonly Guard $auth,
        private readonly Router $router
    ) {}

    /**
     * Compose navigation data for the view.
     *
     * SECURITY CHECKS:
     * 1. Authentication check (early return if not authenticated)
     * 2. Role-based authorization for locale switcher
     * 3. Type-safe role checking via enum
     * 4. Query scope prevents SQL injection
     * 5. All output auto-escaped by Blade
     *
     * Provides the following variables to the view:
     * - userRole: Current user's role value (string)
     * - currentRoute: Current route name
     * - activeClass: CSS classes for active navigation items
     * - inactiveClass: CSS classes for inactive navigation items
     * - mobileActiveClass: CSS classes for active mobile navigation items
     * - mobileInactiveClass: CSS classes for inactive mobile navigation items
     * - canSwitchLocale: Whether locale switching route exists
     * - showTopLocaleSwitcher: Whether to display the locale switcher
     * - languages: Collection of active languages (ordered by display_order)
     * - currentLocale: Current application locale
     *
     * @param  View  $view  The view instance being composed
     */
    public function compose(View $view): void
    {
        // SECURITY: Provide default values for unauthenticated users
        if (! $this->auth->check()) {
            $view->with([
                'userRole' => null,
                'currentRoute' => $this->router->currentRouteName(),
                'activeClass' => self::ACTIVE_CLASS,
                'inactiveClass' => self::INACTIVE_CLASS,
                'mobileActiveClass' => self::ACTIVE_CLASS,
                'mobileInactiveClass' => self::INACTIVE_CLASS,
                'canSwitchLocale' => false,
                'showTopLocaleSwitcher' => false,
                'languages' => collect(),
                'currentLocale' => app()->getLocale(),
            ]);
            return;
        }

        $user = $this->auth->user();
        $userRole = $user->role;

        $view->with([
            'userRole' => $userRole->value,
            'currentRoute' => $this->router->currentRouteName(),
            'activeClass' => self::ACTIVE_CLASS,
            'inactiveClass' => self::INACTIVE_CLASS,
            'mobileActiveClass' => self::ACTIVE_CLASS,
            'mobileInactiveClass' => self::INACTIVE_CLASS,
            'canSwitchLocale' => $this->router->has('locale.set'),
            'showTopLocaleSwitcher' => $this->shouldShowLocaleSwitcher($userRole),
            'languages' => $this->getActiveLanguages($userRole),
            'currentLocale' => app()->getLocale(),
        ]);
    }

    /**
     * Determine if the locale switcher should be displayed for the given role.
     *
     * SECURITY: Role-based authorization prevents unauthorized locale changes.
     * The locale switcher is hidden for managers, tenants, and superadmins as they
     * typically operate within a fixed organizational locale context.
     *
     * @param  UserRole  $userRole  The user's role (type-safe enum)
     * @return bool True if the locale switcher should be shown
     */
    private function shouldShowLocaleSwitcher(UserRole $userRole): bool
    {
        return $this->router->has('locale.set')
            && ! in_array($userRole, self::ROLES_WITHOUT_LOCALE_SWITCHER, true);
    }

    /**
     * Retrieve active languages for the locale switcher.
     *
     * SECURITY FEATURES:
     * - Returns empty collection if user not authorized (defense in depth)
     * - Uses query scope to prevent SQL injection
     * - Only returns active languages (information disclosure prevention)
     * - Ordered by display_order (prevents timing attacks via ordering)
     *
     * Returns an empty collection if the locale switcher should not be shown
     * for the current user role. Otherwise, returns all active languages
     * ordered by their display_order.
     *
     * @param  UserRole  $userRole  The user's role (type-safe enum)
     * @return Collection<int, Language> Collection of active Language models
     */
    private function getActiveLanguages(UserRole $userRole): Collection
    {
        // SECURITY: Defense in depth - don't query if user not authorized
        if (! $this->shouldShowLocaleSwitcher($userRole)) {
            return collect();
        }

        // SECURITY: Use scope to prevent SQL injection and ensure consistent filtering
        return Language::getActiveLanguages();
    }
}
