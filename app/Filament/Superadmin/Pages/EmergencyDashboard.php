<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Pages;

use Filament\Pages\Page;

/**
 * Emergency Dashboard for Superadmin Panel - Filament v4 Compatible
 */
class EmergencyDashboard extends Page
{
    protected static string $view = 'filament.superadmin.pages.emergency-dashboard';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    public function getTitle(): string
    {
        return 'Emergency Dashboard';
    }
}