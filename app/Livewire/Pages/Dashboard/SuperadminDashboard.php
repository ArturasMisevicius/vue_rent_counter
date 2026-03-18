<?php

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SuperadminDashboard extends Component
{
    public function mount(): void
    {
        abort_unless($this->user()->isSuperadmin(), 403);
    }

    public function render(): View
    {
        return view('livewire.pages.dashboard.superadmin-dashboard', [
            'dashboard' => $this->dashboard,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function dashboard(): array
    {
        return array_replace_recursive(
            $this->defaultDashboard(),
            app(PlatformDashboardData::class)->for($this->user()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultDashboard(): array
    {
        return [
            'metrics' => [],
            'revenueByPlan' => [],
            'expiringSubscriptions' => [],
            'recentSecurityViolations' => [],
            'recentOrganizations' => [],
        ];
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
