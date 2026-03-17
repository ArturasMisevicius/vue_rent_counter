<?php

namespace App\Filament\Pages;

use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Models\User;
use Filament\Pages\Page;

class PlatformDashboard extends Page
{
    protected static ?string $slug = 'platform-dashboard';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.platform-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    /**
     * @return array{
     *     metrics: array<int, array{label: string, value: string}>,
     *     revenueByPlan: array<int, array{plan: string, amount: string}>,
     *     expiringSubscriptions: array<int, array{organization: string, plan: string, expires_at: string}>,
     *     recentSecurityViolations: array<int, array{organization: string, summary: string, severity: string}>,
     *     recentOrganizations: array<int, array{name: string, slug: string}>
     * }
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return app(PlatformDashboardData::class)->for($user);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
