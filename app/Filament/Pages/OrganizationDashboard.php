<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class OrganizationDashboard extends Page
{
    protected static ?string $slug = 'organization-dashboard';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.organization-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }
}
