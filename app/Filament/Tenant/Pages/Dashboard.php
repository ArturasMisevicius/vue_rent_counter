<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Enums\UserRole;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant Dashboard Page
 * 
 * Simplified dashboard for tenant users showing their property information,
 * meter readings, and billing overview. Restricted to TENANT role only.
 * 
 * ## Features
 * - Property overview
 * - Recent meter readings
 * - Invoice summary
 * - Quick actions
 * 
 * ## Security
 * - Role-based access control (TENANT only)
 * - Property-scoped data access
 * - Read-only interface for most data
 */
final class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT;
    }

    /**
     * Get widgets for the dashboard.
     * 
     * Simplified widget set for tenant users focusing on essential information.
     * 
     * @return array<string>
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Tenant\Widgets\PropertyStatsWidget::class,
            \App\Filament\Tenant\Widgets\RecentInvoicesWidget::class,
        ];
    }

    /**
     * Get column configuration for responsive layout.
     * 
     * @return array<string, int>|int
     */
    public function getColumns(): array|int
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }
}