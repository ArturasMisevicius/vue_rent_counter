<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use App\Livewire\Concerns\ListensForDashboardRefreshes;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class AdminDashboard extends Component
{
    use ListensForDashboardRefreshes;

    public bool $showSubscriptionUsage = true;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $dashboardData = [];

    #[Locked]
    public int $organizationId = 0;

    public function mount(array $dashboardData = []): void
    {
        $user = $this->user();

        abort_unless($user->isAdmin() || $user->isManager(), 403);

        $this->organizationId = (int) $user->organization_id;
        $this->showSubscriptionUsage = $user->isAdmin();
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
        return view('livewire.pages.dashboard.admin-dashboard', [
            'dashboard' => $this->dashboard,
            'showSubscriptionUsage' => $this->showSubscriptionUsage,
        ]);
    }

    public function refreshDashboardOnInterval(): void
    {
        $refreshedData = app(AdminDashboardStats::class)->dashboardFor($this->user(), 10, 10);

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
        $data = $this->dashboardData ?: app(AdminDashboardStats::class)->dashboardFor($this->user(), 10, 10);

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
            'metrics' => [
                'total_properties' => 0,
                'active_tenants' => 0,
                'pending_invoices' => 0,
                'revenue_this_month' => (string) (new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY))->formatCurrency(0, 'EUR'),
            ],
            'subscription_usage' => [],
            'recent_invoices' => [],
            'upcoming_reading_deadlines' => [],
        ];
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
