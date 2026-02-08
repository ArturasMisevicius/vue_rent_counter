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

    protected function getTableQuery(): Builder
    {
        $cacheKey = 'anomaly_detection_' . auth()->user()->currentTeam->id;
        
        $anomalyData = Cache::remember($cacheKey, 120, function () {
            $auditReporter = app(UniversalServiceAuditReporter::class);
            $report = $auditReporter->generateReport(
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