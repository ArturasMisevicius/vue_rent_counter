<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Livewire\Concerns\ListensForDashboardRefreshes;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class SuperadminDashboard extends Component
{
    use ListensForDashboardRefreshes;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $dashboardData = [];

    public function mount(array $dashboardData = []): void
    {
        abort_unless($this->user()->isSuperadmin(), 403);
        $this->dashboardData = $dashboardData['data'] ?? $dashboardData;
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return $this->dashboardRefreshListeners();
    }

    public function render(): View
    {
        return view('livewire.pages.dashboard.superadmin-dashboard', [
            'dashboard' => $this->dashboard,
        ]);
    }

    public function refreshDashboardOnInterval(): void
    {
        $refreshedData = app(PlatformDashboardData::class)->for($this->user());

        if ($refreshedData === $this->dashboardData) {
            $this->skipRender();

            return;
        }

        $this->dashboardData = $refreshedData;
        unset($this->dashboard);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function dashboard(): array
    {
        $data = $this->dashboardData ?: app(PlatformDashboardData::class)->for($this->user());

        return array_replace_recursive(
            $this->defaultDashboard(),
            $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultDashboard(): array
    {
        return [
            'metrics' => [],
            'revenueByPlan' => [
                'labels' => [],
                'series' => [],
            ],
            'expiringSubscriptions' => [
                'rows' => [],
                'has_more' => false,
                'view_all_url' => '',
            ],
            'recentSecurityViolations' => [],
            'recentOrganizations' => [
                'rows' => [],
                'export_url' => '',
            ],
        ];
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
