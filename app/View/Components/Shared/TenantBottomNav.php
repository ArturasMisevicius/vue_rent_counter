<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TenantBottomNav extends Component
{
    public ?User $user;

    /**
     * @var list<array{key: string, label: string, icon: string, route: string, patterns: list<string>, is_active: bool}>
     */
    public array $items;

    public function __construct()
    {
        $user = auth()->user();
        $this->user = $user instanceof User ? $user : null;
        $this->items = collect($this->navigationItems())
            ->map(fn (array $item): array => [
                ...$item,
                'is_active' => request()->routeIs(...$item['patterns']),
            ])
            ->all();
    }

    public function render(): View
    {
        return view('components.shared.tenant-bottom-nav');
    }

    /**
     * @return list<array{key: string, label: string, icon: string, route: string, patterns: list<string>}>
     */
    private function navigationItems(): array
    {
        return [
            [
                'key' => 'home',
                'label' => __('tenant.navigation.home'),
                'icon' => 'heroicon-m-home',
                'route' => 'filament.admin.pages.dashboard',
                'patterns' => [
                    'filament.admin.pages.dashboard',
                    'filament.admin.pages.tenant-dashboard',
                    'tenant.home',
                ],
            ],
            [
                'key' => 'readings',
                'label' => __('tenant.navigation.readings'),
                'icon' => 'heroicon-m-clipboard-document-list',
                'route' => 'filament.admin.pages.tenant-submit-meter-reading',
                'patterns' => [
                    'filament.admin.pages.tenant-submit-meter-reading',
                    'tenant.readings.create',
                ],
            ],
            [
                'key' => 'property',
                'label' => __('tenant.navigation.property'),
                'icon' => 'heroicon-m-home-modern',
                'route' => 'filament.admin.pages.tenant-property-details',
                'patterns' => [
                    'filament.admin.pages.tenant-property-details',
                    'tenant.property.show',
                ],
            ],
            [
                'key' => 'invoices',
                'label' => __('tenant.navigation.invoices'),
                'icon' => 'heroicon-m-document-text',
                'route' => 'filament.admin.pages.tenant-invoice-history',
                'patterns' => [
                    'filament.admin.pages.tenant-invoice-history',
                    'tenant.invoices.index',
                ],
            ],
        ];
    }
}
