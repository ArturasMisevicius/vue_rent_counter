<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Dashboard;

use App\Filament\Support\Tenant\Portal\TenantHomePresenter;
use App\Livewire\Concerns\ListensForDashboardRefreshes;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TenantDashboard extends Component
{
    use ListensForDashboardRefreshes;
    use ResolvesTenantWorkspace;

    public bool $showIntro = true;

    /**
     * @var array<string, mixed>
     */
    #[Locked]
    public array $summaryData = [];

    #[Locked]
    public int $organizationId = 0;

    public function mount(array $dashboardData = []): void
    {
        $workspace = $this->tenantWorkspace(requireOrganization: false);

        $this->organizationId = (int) $workspace->organizationId;
        $this->summaryData = $dashboardData['data'] ?? $dashboardData;
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
        return view('livewire.pages.dashboard.tenant-dashboard', [
            'summary' => $this->summary,
            'showIntro' => $this->showIntro,
        ]);
    }

    public function refreshSummaryOnInterval(): void
    {
        $refreshedSummary = app(TenantHomePresenter::class)->for($this->tenant());

        if ($refreshedSummary === $this->summaryData) {
            $this->skipRender();

            return;
        }

        $this->summaryData = $refreshedSummary;
        unset($this->summary);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        $data = $this->summaryData ?: app(TenantHomePresenter::class)->for($this->tenant());

        return array_replace_recursive(
            $this->defaultSummary(),
            $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSummary(): array
    {
        return [
            'tenant_name' => '',
            'has_assignment' => false,
            'property_name' => null,
            'property_building_name' => null,
            'property_address' => null,
            'assigned_property' => [
                'name' => null,
                'building' => null,
                'address' => null,
            ],
            'property_url' => route('filament.admin.pages.tenant-property-details'),
            'submit_reading_url' => route('filament.admin.pages.tenant-submit-meter-reading'),
            'has_outstanding_balance' => false,
            'outstanding_label' => __('tenant.status.all_paid_up'),
            'outstanding_total' => 0,
            'outstanding_total_display' => '',
            'outstanding_invoice_count' => 0,
            'payment_guidance' => [
                'content' => null,
                'has_contact_details' => false,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
            ],
            'month_heading' => __('tenant.pages.home.month_heading'),
            'meters_missing_current_month' => 0,
            'current_month_metric' => trans_choice('tenant.pages.home.current_month_metric', 0, [
                'count' => 0,
            ]),
            'current_month_message' => __('tenant.messages.all_current_month'),
            'consumption_by_type' => [],
            'empty_state_title' => __('tenant.pages.home.unassigned_title'),
            'empty_state_description' => __('tenant.pages.home.unassigned_description'),
            'recent_readings' => [],
        ];
    }

    protected function tenant(): User
    {
        return $this->currentTenant();
    }
}
