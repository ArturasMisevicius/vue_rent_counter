<?php

namespace App\Filament\Pages;

use App\Support\Admin\OrganizationContext;
use App\Support\Admin\Reports\ConsumptionReportBuilder;
use App\Support\Admin\Reports\MeterComplianceReportBuilder;
use App\Support\Admin\Reports\OutstandingBalancesReportBuilder;
use App\Support\Admin\Reports\RevenueReportBuilder;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class Reports extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'reports';

    protected string $view = 'filament.pages.reports';

    /**
     * @var array{start_date: string, end_date: string}
     */
    public array $filters = [];

    public string $activeTab = 'consumption';

    public bool $hasLoadedReport = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    public function mount(): void
    {
        $this->filters = [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public function getTitle(): string
    {
        return __('admin.reports.title');
    }

    public function loadReport(string $tab): void
    {
        $this->activeTab = $tab;
        $this->hasLoadedReport = true;

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            $this->rows = [];

            return;
        }

        $start = Carbon::parse($this->filters['start_date'])->startOfDay();
        $end = Carbon::parse($this->filters['end_date'])->endOfDay();

        $this->rows = match ($tab) {
            'revenue' => app(RevenueReportBuilder::class)->handle($organizationId, $start, $end),
            'outstanding' => app(OutstandingBalancesReportBuilder::class)->handle($organizationId, $start, $end),
            'compliance' => app(MeterComplianceReportBuilder::class)->handle($organizationId, $start, $end),
            default => app(ConsumptionReportBuilder::class)->handle($organizationId, $start, $end),
        };

        session()->put('admin.reports.filters', $this->filters);
    }
}
