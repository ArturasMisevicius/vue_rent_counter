<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceAuditReporter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Compliance Status Widget
 * 
 * Displays detailed compliance status for different audit categories
 * with scores, status indicators, and recommendations.
 */
final class ComplianceStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = '300s';

    public function __construct(
        private readonly UniversalServiceAuditReporter $auditReporter,
    ) {
        parent::__construct();
    }

    protected function getTableHeading(): ?string
    {
        return __('dashboard.audit.compliance_status');
    }

    protected function getTableDescription(): ?string
    {
        return __('dashboard.audit.compliance_description');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('category')
                    ->label(__('dashboard.audit.compliance_category'))
                    ->formatStateUsing(fn (string $state): string => __("dashboard.audit.categories.{$state}"))
                    ->sortable(),
                
                TextColumn::make('score')
                    ->label(__('dashboard.audit.compliance_score'))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 95 => 'success',
                        $state >= 80 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label(__('dashboard.audit.status'))
                    ->formatStateUsing(fn (string $state): string => __("dashboard.audit.statuses.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compliant' => 'success',
                        'warning' => 'warning',
                        'non_compliant' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('last_check')
                    ->label(__('dashboard.audit.last_check'))
                    ->dateTime()
                    ->sortable(),
                
                TextColumn::make('issues_count')
                    ->label(__('dashboard.audit.issues'))
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : __('dashboard.audit.no_issues'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success'),
            ])
            ->defaultSort('score', 'desc')
            ->paginated(false);
    }

    /**
     * Get table query with compliance data.
     */
    protected function getTableQuery(): Builder
    {
        $cacheKey = 'compliance_status_' . auth()->user()->currentTeam->id;
        
        $complianceData = Cache::remember($cacheKey, 300, function () {
            $report = $this->auditReporter->generateReport(
                tenantId: auth()->user()->currentTeam->id,
                startDate: now()->subDays(30),
                endDate: now(),
            );
            
            $compliance = $report->complianceStatus;
            
            return [
                [
                    'id' => 1,
                    'category' => 'audit_trail',
                    'score' => $compliance->auditTrailCompleteness['score'] ?? 0,
                    'status' => $this->getComplianceStatus($compliance->auditTrailCompleteness['score'] ?? 0),
                    'last_check' => now(),
                    'issues_count' => count($compliance->auditTrailCompleteness['issues'] ?? []),
                ],
                [
                    'id' => 2,
                    'category' => 'data_retention',
                    'score' => $compliance->dataRetentionCompliance['score'] ?? 0,
                    'status' => $this->getComplianceStatus($compliance->dataRetentionCompliance['score'] ?? 0),
                    'last_check' => now(),
                    'issues_count' => count($compliance->dataRetentionCompliance['issues'] ?? []),
                ],
                [
                    'id' => 3,
                    'category' => 'regulatory',
                    'score' => $compliance->regulatoryCompliance['score'] ?? 0,
                    'status' => $this->getComplianceStatus($compliance->regulatoryCompliance['score'] ?? 0),
                    'last_check' => now(),
                    'issues_count' => count($compliance->regulatoryCompliance['issues'] ?? []),
                ],
                [
                    'id' => 4,
                    'category' => 'security',
                    'score' => $compliance->securityCompliance['score'] ?? 0,
                    'status' => $this->getComplianceStatus($compliance->securityCompliance['score'] ?? 0),
                    'last_check' => now(),
                    'issues_count' => count($compliance->securityCompliance['issues'] ?? []),
                ],
                [
                    'id' => 5,
                    'category' => 'data_quality',
                    'score' => $compliance->dataQualityCompliance['score'] ?? 0,
                    'status' => $this->getComplianceStatus($compliance->dataQualityCompliance['score'] ?? 0),
                    'last_check' => now(),
                    'issues_count' => count($compliance->dataQualityCompliance['issues'] ?? []),
                ],
            ];
        });
        
        // Create a fake Eloquent query builder with the data
        return new class($complianceData) extends Builder {
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
                return $this->get($columns);
            }
        };
    }

    /**
     * Get compliance status based on score.
     */
    private function getComplianceStatus(float $score): string
    {
        return match (true) {
            $score >= 95 => 'compliant',
            $score >= 80 => 'warning',
            default => 'non_compliant',
        };
    }
}