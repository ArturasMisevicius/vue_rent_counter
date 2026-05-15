<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\Reports\ConsumptionReportBuilder;
use App\Filament\Support\Admin\Reports\MeterComplianceReportBuilder;
use App\Filament\Support\Admin\Reports\OutstandingBalancesReportBuilder;
use App\Filament\Support\Admin\Reports\RevenueReportBuilder;
use App\Filament\Widgets\Reports\MeterComplianceStatusChart;
use App\Filament\Widgets\Reports\RevenueMonthlyTotalsChart;
use App\Http\Requests\Admin\Reports\ConsumptionReportRequest;
use App\Http\Requests\Admin\Reports\MeterComplianceReportRequest;
use App\Http\Requests\Admin\Reports\OutstandingBalancesReportRequest;
use App\Http\Requests\Admin\Reports\ReportExportRequest;
use App\Http\Requests\Admin\Reports\RevenueReportRequest;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\ScheduledExportService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as BasePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ReportsPage extends Page
{
    use AppliesShellLocale;
    use WithPagination;

    protected string $view = 'filament.pages.reports';

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'consumption';

    #[Url(as: 'from', history: true)]
    public string $dateFrom = '';

    #[Url(as: 'to', history: true)]
    public string $dateTo = '';

    #[Url(as: 'building', history: true)]
    public ?string $buildingId = null;

    #[Url(as: 'property', history: true)]
    public ?string $propertyId = null;

    #[Url(as: 'tenant', history: true)]
    public ?string $tenantId = null;

    #[Url(as: 'meter', history: true)]
    public ?string $meterType = null;

    #[Url(as: 'status', history: true)]
    public ?string $statusFilter = null;

    #[Url(as: 'org', history: true)]
    public ?string $reportOrganizationId = null;

    #[Locked]
    public ?int $organizationId = null;

    public int $perPage = 10;

    public function mount(OrganizationContext $organizationContext): void
    {
        abort_unless(static::canAccess(), 403);

        $this->organizationId = $organizationContext->currentOrganizationId();
        $this->reportOrganizationId = $this->defaultReportOrganizationId();
        $this->dateFrom = $this->dateFrom !== '' ? $this->dateFrom : now()->startOfMonth()->toDateString();
        $this->dateTo = $this->dateTo !== '' ? $this->dateTo : now()->endOfMonth()->toDateString();
        $this->activeTab = $this->normalizedTab($this->activeTab);
        $this->statusFilter = $this->normalizedStatusFilter($this->statusFilter);
    }

    public static function canAccess(): bool
    {
        $user = request()->user();

        return $user instanceof User && $user->isAdminLike();
    }

    protected function getHeaderWidgets(): array
    {
        $organizationId = $this->resolvedOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return [
            RevenueMonthlyTotalsChart::make([
                'organizationId' => $organizationId,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ]),
            MeterComplianceStatusChart::make([
                'organizationId' => $organizationId,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'buildingId' => $this->buildingId,
                'propertyId' => $this->propertyId,
                'tenantId' => $this->tenantId,
                'meterType' => $this->meterType,
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 1,
            'xl' => 2,
        ];
    }

    public function updated(string $name): void
    {
        if ($name === 'activeTab') {
            $this->activeTab = $this->normalizedTab($this->activeTab);
            $this->statusFilter = $this->normalizedStatusFilter($this->statusFilter);
        }

        if ($name === 'reportOrganizationId') {
            $this->reportOrganizationId = $this->normalizedReportOrganizationId($this->reportOrganizationId);
            $this->buildingId = null;
            $this->propertyId = null;
            $this->tenantId = null;
            $this->meterType = null;
        }

        if (in_array($name, [
            'activeTab',
            'dateFrom',
            'dateTo',
            'reportOrganizationId',
            'buildingId',
            'propertyId',
            'tenantId',
            'meterType',
            'statusFilter',
        ], true)) {
            $this->resetPage();
        }
    }

    public function exportCsv(ScheduledExportService $scheduledExportService): void
    {
        $report = $this->exportReport('csv');

        $scheduledExportService->scheduleExport(
            filename: $this->exportFilename('csv'),
            format: 'csv',
            title: $report['title'],
            summary: $report['summary'],
            columns: $report['columns'],
            rows: $report['rows'],
            emptyState: $report['empty_state'],
            requestedBy: $this->user(),
        );

        Notification::make()
            ->success()
            ->title(__('admin.reports.messages.export_queued'))
            ->send();
    }

    public function exportPdf(ScheduledExportService $scheduledExportService): void
    {
        $report = $this->exportReport('pdf');

        $scheduledExportService->scheduleExport(
            filename: $this->exportFilename('pdf'),
            format: 'pdf',
            title: $report['title'],
            summary: $report['summary'],
            columns: $report['columns'],
            rows: $report['rows'],
            emptyState: $report['empty_state'],
            requestedBy: $this->user(),
        );

        Notification::make()
            ->success()
            ->title(__('admin.reports.messages.export_queued'))
            ->send();
    }

    public function sendReminder(int $invoiceId, SendInvoiceReminderAction $sendInvoiceReminderAction): void
    {
        abort_if($this->organizationId === null, 403);

        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'status',
                'currency',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'billing_period_start',
                'billing_period_end',
                'due_date',
                'last_reminder_sent_at',
            ])
            ->forOrganization($this->organizationId)
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number',
                'property.building:id,organization_id,name',
            ])
            ->findOrFail($invoiceId);

        $queued = $sendInvoiceReminderAction->handle($invoice, $this->user());

        unset($this->report);

        if (! $queued) {
            Notification::make()
                ->warning()
                ->title(__('admin.reports.messages.reminder_skipped'))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('admin.reports.messages.reminder_queued'))
            ->send();
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset(
            $this->tabs,
            $this->report,
            $this->rows,
            $this->organizationOptions,
            $this->buildingOptions,
            $this->propertyOptions,
            $this->tenantOptions,
        );
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function tabs(): array
    {
        return [
            'consumption' => __('admin.reports.tabs.consumption'),
            'revenue' => __('admin.reports.tabs.revenue'),
            'outstanding_balances' => __('admin.reports.tabs.outstanding_balances'),
            'meter_compliance' => __('admin.reports.tabs.meter_compliance'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function report(): array
    {
        $organizationId = $this->resolvedOrganizationId();

        if ($organizationId === null) {
            return [
                'title' => __('admin.reports.title'),
                'description' => __('admin.reports.messages.organization_context_required'),
                'summary' => [],
                'columns' => [],
                'rows' => [],
                'empty_state' => __('admin.reports.messages.organization_context_required'),
            ];
        }

        $filters = $this->validatedFilters();
        $startDate = Carbon::parse((string) $filters['start_date']);
        $endDate = Carbon::parse((string) $filters['end_date']);

        return match ($this->activeTab) {
            'revenue' => app(RevenueReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            'outstanding_balances' => app(OutstandingBalancesReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            'meter_compliance' => app(MeterComplianceReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            default => app(ConsumptionReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
        };
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = collect($this->report['rows'] ?? []);
        $page = $this->getPage();

        return new Paginator(
            items: $rows->forPage($page, $this->perPage)->values(),
            total: $rows->count(),
            perPage: $this->perPage,
            currentPage: $page,
            options: [
                'path' => BasePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function buildingOptions(): array
    {
        $organizationId = $this->resolvedOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return Building::query()
            ->select(['id', 'organization_id', 'name'])
            ->forOrganization($organizationId)
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Building $building): array => [(string) $building->id => $building->displayName()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function propertyOptions(): array
    {
        $organizationId = $this->resolvedOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
            ->forOrganization($organizationId)
            ->when(
                filled($this->buildingId),
                fn ($query) => $query->where('building_id', (int) $this->buildingId),
            )
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                (string) $property->id => trim(implode(' · ', array_filter([
                    $property->displayName(),
                    $property->unit_number,
                ]))),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function tenantOptions(): array
    {
        $organizationId = $this->resolvedOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'role'])
            ->forOrganization($organizationId)
            ->tenants()
            ->when(
                filled($this->propertyId),
                fn ($query) => $query->whereHas('currentPropertyAssignment', fn ($assignmentQuery) => $assignmentQuery->where('property_id', (int) $this->propertyId)),
            )
            ->orderedByName()
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $label, int|string $id): array => [(string) $id => $label])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function meterTypeOptions(): array
    {
        return MeterType::options();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function organizationOptions(): array
    {
        if (! $this->canSelectOrganization()) {
            return [];
        }

        return Organization::query()
            ->select(['id', 'name'])
            ->ordered()
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $label, int|string $id): array => [(string) $id => $label])
            ->all();
    }

    public function canSelectOrganization(): bool
    {
        return $this->organizationId === null && $this->user()->isSuperadmin();
    }

    public function hasOrganizationContext(): bool
    {
        return $this->resolvedOrganizationId() !== null;
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return match ($this->activeTab) {
            'revenue' => ['all' => __('admin.reports.filters.all'), ...InvoiceStatus::options()],
            'outstanding_balances' => [
                'all' => __('admin.reports.filters.all'),
                'finalized' => __('admin.invoices.statuses.finalized'),
                'overdue' => __('admin.invoices.statuses.overdue'),
            ],
            'meter_compliance' => [
                'all' => __('admin.reports.filters.all'),
                'compliant' => __('admin.reports.states.compliant'),
                'needs_attention' => __('admin.reports.states.needs_attention'),
                'missing' => __('admin.reports.states.missing'),
            ],
            default => ['all' => __('admin.reports.filters.all')],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function exportReport(string $format): array
    {
        abort_if($this->resolvedOrganizationId() === null, 403);

        $request = new ReportExportRequest;
        $request->validatePayload([
            ...$this->validatedFilters(),
            'format' => $format,
        ], $this->user());

        return $this->report;
    }

    private function exportFilename(string $extension): string
    {
        return 'reports-'.Str::slug($this->activeTab).'-'.$this->dateFrom.'-to-'.$this->dateTo.'.'.$extension;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(): array
    {
        $statusFilter = $this->normalizedStatusFilter($this->statusFilter);

        $payload = [
            'start_date' => $this->dateFrom,
            'end_date' => $this->dateTo,
            'building_id' => $this->buildingId,
            'property_id' => $this->propertyId,
            'tenant_id' => $this->tenantId,
            'meter_type' => $this->meterType,
            'invoice_status' => $this->activeTab === 'revenue' && $statusFilter !== 'all'
                ? $statusFilter
                : null,
            'only_overdue' => $statusFilter === 'overdue',
            'compliance_state' => in_array($statusFilter, ['compliant', 'needs_attention', 'missing'], true)
                ? $statusFilter
                : null,
            'status_filter' => $statusFilter,
        ];

        $validated = $this->currentFiltersRequest()->validatePayload($payload, $this->user());
        $validated['building_id'] = isset($validated['building_id']) ? (int) $validated['building_id'] : null;
        $validated['property_id'] = isset($validated['property_id']) ? (int) $validated['property_id'] : null;
        $validated['tenant_id'] = isset($validated['tenant_id']) ? (int) $validated['tenant_id'] : null;

        return $validated;
    }

    private function currentFiltersRequest(): ConsumptionReportRequest|RevenueReportRequest|OutstandingBalancesReportRequest|MeterComplianceReportRequest
    {
        return match ($this->activeTab) {
            'revenue' => new RevenueReportRequest,
            'outstanding_balances' => new OutstandingBalancesReportRequest,
            'meter_compliance' => new MeterComplianceReportRequest,
            default => new ConsumptionReportRequest,
        };
    }

    private function normalizedTab(string $tab): string
    {
        return array_key_exists($tab, $this->tabs())
            ? $tab
            : 'consumption';
    }

    private function normalizedStatusFilter(?string $statusFilter): string
    {
        $resolved = filled($statusFilter) ? (string) $statusFilter : 'all';

        return array_key_exists($resolved, $this->statusOptions())
            ? $resolved
            : 'all';
    }

    private function resolvedOrganizationId(): ?int
    {
        if ($this->organizationId !== null) {
            return $this->organizationId;
        }

        if (! $this->canSelectOrganization()) {
            return null;
        }

        return filled($this->reportOrganizationId)
            ? (int) $this->reportOrganizationId
            : null;
    }

    private function defaultReportOrganizationId(): ?string
    {
        if (! $this->canSelectOrganization()) {
            return null;
        }

        $selectedOrganizationId = $this->normalizedReportOrganizationId($this->reportOrganizationId);

        if ($selectedOrganizationId !== null) {
            return $selectedOrganizationId;
        }

        $loginDemoOrganizationId = Organization::query()
            ->select(['id'])
            ->where('slug', 'tenanto-demo-organization')
            ->value('id');

        if ($loginDemoOrganizationId !== null) {
            return (string) $loginDemoOrganizationId;
        }

        $firstOrganizationId = Organization::query()
            ->select(['id'])
            ->ordered()
            ->value('id');

        return $firstOrganizationId !== null ? (string) $firstOrganizationId : null;
    }

    private function normalizedReportOrganizationId(?string $organizationId): ?string
    {
        if (! $this->canSelectOrganization()) {
            return null;
        }

        if (! filled($organizationId)) {
            return null;
        }

        $exists = Organization::query()
            ->select(['id'])
            ->whereKey((int) $organizationId)
            ->exists();

        return $exists ? (string) ((int) $organizationId) : null;
    }

    private function user(): User
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
