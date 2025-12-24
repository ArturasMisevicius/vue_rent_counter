<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceAuditReporter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Anomaly Detection Widget
 * 
 * Displays detected anomalies in audit data with severity levels,
 * descriptions, and action buttons for investigation and resolution.
 */
final class AnomalyDetectionWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected static ?string $pollingInterval = '120s';

    public function __construct(
        private readonly UniversalServiceAuditReporter $auditReporter,
    ) {
        parent::__construct();
    }

    protected function getTableHeading(): ?string
    {
        return __('dashboard.audit.anomaly_detection');
    }

    protected function getTableDescription(): ?string
    {
        return __('dashboard.audit.anomaly_description');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('type')
                    ->label(__('dashboard.audit.anomaly_type'))
                    ->formatStateUsing(fn (string $state): string => __("dashboard.audit.anomaly_types.{$state}"))
                    ->sortable(),
                
                TextColumn::make('severity')
                    ->label(__('dashboard.audit.severity'))
                    ->formatStateUsing(fn (string $state): string => __("dashboard.audit.severities.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label(__('dashboard.audit.description'))
                    ->limit(50)
                    ->tooltip(fn ($record): string => $record->description),
                
                TextColumn::make('detected_at')
                    ->label(__('dashboard.audit.detected_at'))
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label(__('dashboard.audit.status'))
                    ->formatStateUsing(fn (string $state): string => __("dashboard.audit.anomaly_statuses.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'resolved' => 'success',
                        'investigating' => 'warning',
                        'new' => 'danger',
                        'ignored' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('investigate')
                    ->label(__('dashboard.audit.investigate'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(function ($record) {
                        // Mark as investigating and redirect to detailed view
                        $this->markAsInvestigating($record);
                    })
                    ->visible(fn ($record): bool => $record->status === 'new'),
                
                Action::make('resolve')
                    ->label(__('dashboard.audit.resolve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $this->markAsResolved($record);
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['new', 'investigating'])),
                
                Action::make('ignore')
                    ->label(__('dashboard.audit.ignore'))
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $this->markAsIgnored($record);
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['new', 'investigating'])),
                
                Action::make('view_details')
                    ->label(__('dashboard.audit.view_details'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalContent(fn ($record) => view('filament.tenant.modals.anomaly-details', [
                        'anomaly' => $record,
                    ]))
                    ->modalHeading(fn ($record): string => __('dashboard.audit.anomaly_details_title', [
                        'type' => __("dashboard.audit.anomaly_types.{$record->type}"),
                    ]))
                    ->modalWidth('lg'),
            ])
            ->defaultSort('detected_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    /**
     * Get table query with anomaly data.
     */
    protected function getTableQuery(): Builder
    {
        $cacheKey = 'anomaly_detection_' . auth()->user()->currentTeam->id;
        
        $anomalyData = Cache::remember($cacheKey, 120, function () {
            $report = $this->auditReporter->generateReport(
                tenantId: auth()->user()->currentTeam->id,
                startDate: now()->subDays(7), // Last 7 days for anomalies
                endDate: now(),
            );
            
            return collect($report->anomalies)->map(function ($anomaly, $index) {
                return [
                    'id' => $index + 1,
                    'type' => $anomaly['type'],
                    'severity' => $anomaly['severity'],
                    'description' => $anomaly['description'],
                    'detected_at' => $anomaly['detected_at'],
                    'status' => $this->getAnomalyStatus($anomaly),
                    'details' => $anomaly['details'] ?? [],
                ];
            })->toArray();
        });
        
        // Create a fake Eloquent query builder with the data
        return new class($anomalyData) extends Builder {
            public function __construct(private array $data)
            {
                // Mock builder - we'll override the methods we need
            }
            
            public function get($columns = ['*'])
            {
                return collect($this->data)->map(function ($item) {
                    return (object) $item;
                });
            }
            
            public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
            {
                $collection = $this->get($columns);
                $perPage = $perPage ?: 10;
                $page = $page ?: 1;
                
                return $collection->slice(($page - 1) * $perPage, $perPage);
            }
            
            public function orderBy($column, $direction = 'asc')
            {
                if ($direction === 'desc') {
                    $this->data = collect($this->data)->sortByDesc($column)->values()->toArray();
                } else {
                    $this->data = collect($this->data)->sortBy($column)->values()->toArray();
                }
                return $this;
            }
        };
    }

    /**
     * Get anomaly status (simplified for demo).
     */
    private function getAnomalyStatus(array $anomaly): string
    {
        // In a real implementation, this would check a database table
        // for anomaly status tracking
        return match ($anomaly['severity']) {
            'critical' => 'new',
            'high' => 'investigating',
            'medium' => 'new',
            'low' => 'ignored',
            default => 'new',
        };
    }

    /**
     * Mark anomaly as investigating.
     */
    private function markAsInvestigating($record): void
    {
        // In a real implementation, this would update the anomaly status in the database
        // For now, we'll just clear the cache to refresh the data
        Cache::forget('anomaly_detection_' . auth()->user()->currentTeam->id);
        
        $this->dispatch('anomaly-status-updated', [
            'message' => __('dashboard.audit.anomaly_marked_investigating'),
        ]);
    }

    /**
     * Mark anomaly as resolved.
     */
    private function markAsResolved($record): void
    {
        // In a real implementation, this would update the anomaly status in the database
        Cache::forget('anomaly_detection_' . auth()->user()->currentTeam->id);
        
        $this->dispatch('anomaly-status-updated', [
            'message' => __('dashboard.audit.anomaly_resolved'),
        ]);
    }

    /**
     * Mark anomaly as ignored.
     */
    private function markAsIgnored($record): void
    {
        // In a real implementation, this would update the anomaly status in the database
        Cache::forget('anomaly_detection_' . auth()->user()->currentTeam->id);
        
        $this->dispatch('anomaly-status-updated', [
            'message' => __('dashboard.audit.anomaly_ignored'),
        ]);
    }
}