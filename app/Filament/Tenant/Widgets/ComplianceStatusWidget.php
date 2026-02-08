<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceAuditReporter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Compliance Status Widget
 *
 * Displays tenant-level compliance breakdown for key audit categories.
 */
final class ComplianceStatusWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getComplianceRows())
            ->columns([
                TextColumn::make('category')
                    ->label(__('dashboard.audit.labels.category'))
                    ->formatStateUsing(fn (string $state): string => $this->formatCategory($state))
                    ->weight('medium'),
                TextColumn::make('score')
                    ->label(__('dashboard.audit.compliance_score'))
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1) . '%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 95 => 'success',
                        $state >= 80 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('status')
                    ->label(__('dashboard.audit.status'))
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compliant' => 'success',
                        'warning' => 'warning',
                        'non_compliant' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('issues_count')
                    ->label(__('dashboard.audit.issues'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success'),
                TextColumn::make('last_check')
                    ->label(__('dashboard.audit.last_check'))
                    ->dateTime('M j, Y H:i'),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('dashboard.audit.no_data'));
    }

    private function getComplianceRows(): Collection
    {
        $tenantId = auth()->user()?->currentTeam?->id;
        $cacheKey = 'compliance_status_rows:' . ($tenantId ?? 'system');

        return Cache::remember($cacheKey, 300, function () use ($tenantId): Collection {
            $report = app(UniversalServiceAuditReporter::class)->generateReport(
                tenantId: $tenantId,
                startDate: now()->subDays(30),
                endDate: now(),
            );

            $compliance = $report->complianceStatus;

            $rows = [
                ['category' => 'audit_trail', 'data' => $compliance->auditTrailCompleteness],
                ['category' => 'data_retention', 'data' => $compliance->dataRetentionCompliance],
                ['category' => 'regulatory', 'data' => $compliance->regulatoryCompliance],
                ['category' => 'security', 'data' => $compliance->securityCompliance],
                ['category' => 'data_quality', 'data' => $compliance->dataQualityCompliance],
            ];

            return collect($rows)->map(function (array $row, int $index) use ($report): array {
                $score = (float) ($row['data']['score'] ?? 0);
                $status = (string) ($row['data']['status'] ?? $this->resolveStatus($score));
                $issues = (array) ($row['data']['issues'] ?? []);

                return [
                    '__key' => (string) ($index + 1),
                    'category' => $row['category'],
                    'score' => $score,
                    'status' => $status,
                    'issues_count' => count($issues),
                    'last_check' => $report->generatedAt,
                ];
            });
        });
    }

    private function resolveStatus(float $score): string
    {
        return match (true) {
            $score >= 95 => 'compliant',
            $score >= 80 => 'warning',
            default => 'non_compliant',
        };
    }

    private function formatCategory(string $category): string
    {
        return ucfirst(str_replace('_', ' ', $category));
    }
}
