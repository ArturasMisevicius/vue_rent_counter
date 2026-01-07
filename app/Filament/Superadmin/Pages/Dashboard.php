<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Pages;

use App\Models\User;
use App\Models\Subscription;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Superadmin Dashboard Page
 * 
 * Main dashboard for superadmin users with system-wide overview
 * and management capabilities.
 * 
 * @package App\Filament\Superadmin\Pages
 */
final class Dashboard extends BaseDashboard
{
    /**
     * Get the page title.
     * 
     * @return string
     */
    public function getTitle(): string
    {
        return __('app.pages.superadmin_dashboard');
    }

    /**
     * Get the navigation label.
     * 
     * @return string
     */
    public static function getNavigationLabel(): string
    {
        return __('app.navigation.dashboard');
    }

    /**
     * Get total number of organizations.
     * 
     * @return int
     */
    public function getTotalOrganizations(): int
    {
        // For now, return a placeholder count
        // This will be replaced with actual organization model when implemented
        return 0;
    }

    /**
     * Get number of active subscriptions.
     * 
     * @return int
     */
    public function getActiveSubscriptions(): int
    {
        return Subscription::where('status', 'active')->count();
    }

    /**
     * Get total number of users.
     * 
     * @return int
     */
    public function getTotalUsers(): int
    {
        return User::count();
    }
}