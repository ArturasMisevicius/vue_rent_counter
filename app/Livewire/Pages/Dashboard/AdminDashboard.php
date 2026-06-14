<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Support\Admin\Dashboard\BuildAdminAttentionDashboard;
use App\Filament\Support\Dashboard\DashboardCacheService;
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
        $refreshedData = $this->buildDashboardData();

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
        $data = $this->dashboardData ?: $this->buildDashboardData();

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
            'summary' => [
                'organization_name' => __('dashboard.not_available'),
                'billing_period' => __('dashboard.attention.empty.no_period'),
                'billing_completion' => 0,
                'has_urgent_actions' => false,
                'empty_title' => __('dashboard.attention.empty.no_urgent_actions_title'),
                'empty_description' => __('dashboard.attention.empty.no_urgent_actions_description'),
            ],
            'top_cards' => [],
            'billing_cards' => [],
            'tenant_onboarding_cards' => [],
            'configuration_health_cards' => [],
            'contract_cards' => [],
            'document_cards' => [],
            'data_integrity_cards' => [],
            'needs_action_items' => [],
            'billing_progress' => [
                'period' => __('dashboard.attention.empty.no_period'),
                'completion' => 0,
                'total_invoices' => 0,
                'stages' => [],
            ],
            'recent_activity' => [],
            'visible_widgets' => [],
            'counts' => [],
        ];
    }

    protected function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDashboardData(): array
    {
        $user = $this->user();

        return app(DashboardCacheService::class)->remember(
            $user,
            'admin-attention-dashboard',
            fn (): array => app(BuildAdminAttentionDashboard::class)
                ->handle($this->organizationId, $user->id)
                ->toArray(),
        );
    }
}
