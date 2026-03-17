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
            'eyebrow' => __('dashboard.platform_eyebrow'),
            'heading' => __('dashboard.platform_heading'),
            'description' => __('dashboard.platform_body'),
            'actions' => [
                [
                    'label' => __('superadmin.organizations.plural'),
                    'url' => route('filament.admin.resources.organizations.index'),
                ],
                [
                    'label' => __('shell.navigation.items.subscriptions'),
                    'url' => route('filament.admin.resources.subscriptions.index'),
                ],
            ],
            'showTenantSummary' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function workspaceContext(): array
    {
        return [
            'eyebrow' => __('dashboard.organization_eyebrow'),
            'heading' => __('dashboard.organization_heading'),
            'description' => __('dashboard.organization_body'),
            'actions' => [
                [
                    'label' => __('admin.properties.plural'),
                    'url' => route('filament.admin.resources.properties.index'),
                ],
                [
                    'label' => __('shell.navigation.items.reports'),
                    'url' => route('filament.admin.pages.reports'),
                ],
            ],
            'showTenantSummary' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function tenantContext(): array
    {
        return [
            'eyebrow' => __('dashboard.tenant_eyebrow'),
            'heading' => __('dashboard.tenant_heading'),
            'description' => __('dashboard.tenant_body'),
            'actions' => [
                [
                    'label' => __('tenant.navigation.invoices'),
                    'url' => route('tenant.invoices.index'),
                ],
                [
                    'label' => __('tenant.navigation.readings'),
                    'url' => route('tenant.readings.create'),
                ],
            ],
            'showTenantSummary' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultContext(): array
    {
        return [
            'eyebrow' => __('dashboard.title'),
            'heading' => __('dashboard.title'),
            'description' => __('dashboard.not_available'),
            'actions' => [],
            'showTenantSummary' => false,
        ];
    }
}
