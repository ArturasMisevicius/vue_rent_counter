<?php

namespace App\Filament\Pages;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Models\User;
use App\Support\Admin\OrganizationContext;
use App\Support\Admin\Reports\ConsumptionReportBuilder;
use App\Support\Admin\Reports\MeterComplianceReportBuilder;
use App\Support\Admin\Reports\OutstandingBalancesReportBuilder;
use App\Support\Admin\Reports\ReportPdfExporter;
use App\Support\Admin\Reports\RevenueReportBuilder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reports extends Page
{
    protected static ?string $slug = 'reports';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.reports';

    public string $activeTab = 'consumption';

    /**
     * @var array{
     *     start_date: string,
     *     end_date: string,
     *     meter_type: string,
     *     invoice_status: string,
     *     only_overdue: bool,
     *     compliance_state: string
     * }
     */
    public array $filters = [];

    public bool $hasLoadedReport = false;

    /**
     * @var array{
     *     title: string,
     *     description: string,
     *     summary: array<int, array{label: string, value: string}>,
     *     columns: array<int, array{key: string, label: string}>,
     *     rows: array<int, array<string, string>>,
     *     empty_state: string
     * }|null
     */
    public ?array $report = null;

    public function mount(): void
    {
        app()->setLocale($this->user()->locale);

        $this->filters = $this->defaultFilters();
        $this->restoreState();

        if ($this->hasLoadedReport) {
            $this->report = $this->buildReport();
        }
    }

    public function getTitle(): string
    {
        return __('admin.reports.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'consumption' => __('admin.reports.tabs.consumption'),
            'revenue' => __('admin.reports.tabs.revenue'),
            'outstanding_balances' => __('admin.reports.tabs.outstanding_balances'),
            'meter_compliance' => __('admin.reports.tabs.meter_compliance'),
        ];
    }

    public function switchTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabs())) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetLoadedReport();
        $this->persistState();
    }

    public function updatedFilters(): void
    {
        $this->resetLoadedReport();
        $this->persistState();
    }

    public function loadReport(): void
    {
        $this->report = $this->buildReport();
        $this->hasLoadedReport = true;
        $this->persistState();
    }

    public function exportCsv(): ?StreamedResponse
    {
        $report = $this->currentReport();

        if ($report === null) {
            return null;
        }

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [$report['title']]);

            foreach ($report['summary'] as $item) {
                fputcsv($handle, [$item['label'], $item['value']]);
            }

            fputcsv($handle, []);
            fputcsv($handle, array_map(fn (array $column): string => $column['label'], $report['columns']));

            foreach ($report['rows'] as $row) {
                fputcsv($handle, array_map(
                    fn (array $column): string => $row[$column['key']] ?? '',
                    $report['columns'],
                ));
            }

            fclose($handle);
        }, $this->exportFilename('csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(ReportPdfExporter $reportPdfExporter): ?Response
    {
        $report = $this->currentReport();

        if ($report === null) {
            return null;
        }

        return response()->streamDownload(function () use ($report, $reportPdfExporter): void {
            echo $reportPdfExporter->render(
                $report['title'],
                $report['summary'],
                $report['columns'],
                $report['rows'],
                $report['empty_state'],
            );
        }, $this->exportFilename('pdf'), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function meterTypeOptions(): array
    {
        return collect(MeterType::cases())
            ->mapWithKeys(fn (MeterType $meterType): array => [
                $meterType->value => __('admin.meters.types.'.$meterType->value),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function invoiceStatusOptions(): array
    {
        return collect(InvoiceStatus::cases())
            ->mapWithKeys(fn (InvoiceStatus $status): array => [
                $status->value => __('admin.invoices.statuses.'.$status->value),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function complianceStateOptions(): array
    {
        return [
            'compliant' => __('admin.reports.states.compliant'),
            'needs_attention' => __('admin.reports.states.needs_attention'),
            'missing' => __('admin.reports.states.missing'),
        ];
    }

    protected function buildReport(): array
    {
        $filters = $this->validatedFilters();
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);

        abort_if($organizationId === null, 403);

        return match ($this->activeTab) {
            'consumption' => app(ConsumptionReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            'revenue' => app(RevenueReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            'outstanding_balances' => app(OutstandingBalancesReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            'meter_compliance' => app(MeterComplianceReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
            default => app(ConsumptionReportBuilder::class)->build($organizationId, $startDate, $endDate, $filters),
        };
    }

    private function currentReport(): ?array
    {
        if (! $this->hasLoadedReport) {
            Notification::make()
                ->warning()
                ->title(__('admin.reports.messages.load_before_export'))
                ->send();

            return null;
        }

        return $this->buildReport();
    }

    private function exportFilename(string $extension): string
    {
        return 'reports-'.Str::slug($this->activeTab).'-'.$this->filters['start_date'].'-to-'.$this->filters['end_date'].'.'.$extension;
    }

    /**
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     meter_type: string|null,
     *     invoice_status: string|null,
     *     only_overdue: bool,
     *     compliance_state: string|null
     * }
     */
    private function validatedFilters(): array
    {
        /** @var array{
         *     start_date: Carbon,
         *     end_date: Carbon,
         *     meter_type: string|null,
         *     invoice_status: string|null,
         *     only_overdue: bool,
         *     compliance_state: string|null
         * } $validated
         */
        $validated = Validator::make([
            'start_date' => $this->filters['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $this->filters['end_date'] ?? now()->endOfMonth()->toDateString(),
            'meter_type' => filled($this->filters['meter_type'] ?? null) ? $this->filters['meter_type'] : null,
            'invoice_status' => filled($this->filters['invoice_status'] ?? null) ? $this->filters['invoice_status'] : null,
            'only_overdue' => (bool) ($this->filters['only_overdue'] ?? false),
            'compliance_state' => filled($this->filters['compliance_state'] ?? null) ? $this->filters['compliance_state'] : null,
        ], [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'meter_type' => ['nullable', Rule::in(array_keys($this->meterTypeOptions()))],
            'invoice_status' => ['nullable', Rule::in(array_keys($this->invoiceStatusOptions()))],
            'only_overdue' => ['required', 'boolean'],
            'compliance_state' => ['nullable', Rule::in(array_keys($this->complianceStateOptions()))],
        ])->validate();

        $validated['start_date'] = Carbon::parse($validated['start_date']);
        $validated['end_date'] = Carbon::parse($validated['end_date']);

        return $validated;
    }

    /**
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     meter_type: string,
     *     invoice_status: string,
     *     only_overdue: bool,
     *     compliance_state: string
     * }
     */
    private function defaultFilters(): array
    {
        return [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'meter_type' => '',
            'invoice_status' => '',
            'only_overdue' => false,
            'compliance_state' => '',
        ];
    }

    private function restoreState(): void
    {
        $state = session()->get($this->sessionKey(), []);

        if (! is_array($state)) {
            return;
        }

        $activeTab = $state['active_tab'] ?? null;

        if (is_string($activeTab) && array_key_exists($activeTab, $this->tabs())) {
            $this->activeTab = $activeTab;
        }

        if (is_array($state['filters'] ?? null)) {
            $this->filters = array_replace($this->defaultFilters(), $state['filters']);
        }

        $this->hasLoadedReport = (bool) ($state['has_loaded_report'] ?? false);
    }

    private function persistState(): void
    {
        session()->put($this->sessionKey(), [
            'active_tab' => $this->activeTab,
            'filters' => $this->filters,
            'has_loaded_report' => $this->hasLoadedReport,
        ]);
    }

    private function resetLoadedReport(): void
    {
        $this->hasLoadedReport = false;
        $this->report = null;
    }

    private function sessionKey(): string
    {
        return 'filament.admin.reports.'.$this->user()->id;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
