<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    /**
     * @return array{context: array<string, mixed>}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'context' => match ($user?->role) {
                UserRole::SUPERADMIN => $this->superadminContext(),
                UserRole::ADMIN, UserRole::MANAGER => $this->workspaceContext(),
                UserRole::TENANT => $this->tenantContext(),
                default => $this->defaultContext(),
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function superadminContext(): array
    {
        return [
            'dashboardComponent' => 'pages.dashboard.superadmin-dashboard',
            'dashboardKey' => 'superadmin',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspaceContext(): array
    {
        return [
            'dashboardComponent' => 'pages.dashboard.admin-dashboard',
            'dashboardKey' => 'workspace',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function tenantContext(): array
    {
        return [
            'dashboardComponent' => 'tenant.tenant-dashboard',
            'dashboardKey' => 'tenant',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultContext(): array
    {
        return [
            'dashboardComponent' => null,
            'dashboardKey' => 'default',
        ];
    }
}
