<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\ConfigurationRollbackService;
use App\Services\Audit\UniversalServiceChangeTracker;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

/**
 * Configuration Rollback Widget
 * 
 * Provides rollback management interface for universal service configurations
 * with validation, impact analysis, and rollback history tracking.
 */
final class ConfigurationRollbackWidget extends BaseWidget
{
    protected static ?string $heading = null;
    
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Collection
    {
        $tenantId = auth()->user()->currentTeam->id;
        
        $rollbackService = app(ConfigurationRollbackService::class);
        return $rollbackService->getRollbackCandidates($tenantId);
                ->modalHeading(__('dashboard.audit.rollback_history'))
                ->modalContent(fn () => view('filament.tenant.modals.rollback-history', [
                    'rollbacks' => $this->getRollbackHistory(),
                ]))
                ->modalWidth('5xl'),
            
            Action::make('bulk_rollback')
                ->label(__('dashboard.audit.actions.bulk_rollback'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->form([
                    Select::make('audit_ids')
                        ->label(__('dashboard.audit.labels.select_changes'))
                        ->multiple()
                        ->options($this->getRollbackableChanges())
                        ->required(),
                    
                    Textarea::make('reason')
                        ->label(__('dashboard.audit.labels.rollback_reason'))
                        ->required()
                        ->placeholder(__('dashboard.audit.placeholders.bulk_rollback_reason')),
                ])
                ->action(function (array $data) {
                    return $this->performBulkRollback($data);
                })
                ->requiresConfirmation()
                ->modalHeading(__('dashboard.audit.bulk_rollback_confirmation'))
                ->modalDescription(__('dashboard.audit.bulk_rollback_warning')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('performed_at')
                    ->label(__('dashboard.audit.labels.performed_at'))
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                
                TextColumn::make('model_info')
                    ->label(__('dashboard.audit.labels.configuration'))
                    ->formatStateUsing(function ($record): string {
                        $modelType = class_basename($record['model_type'] ?? 'Unknown');
                        $modelId = $record['model_id'] ?? 'N/A';
                        return "{$modelType} #{$modelId}";
                    })
                    ->badge()
                    ->color('primary'),
                
                TextColumn::make('performed_by')
                    ->label(__('dashboard.audit.labels.performed_by'))
                    ->formatStateUsing(function (?int $state): string {
                        if (!$state) {
                            return __('dashboard.audit.labels.system');
                        }
                        
                        $user = \App\Models\User::find($state);
                        return $user ? $user->name : __('dashboard.audit.labels.unknown_user');
                    }),
                
                TextColumn::make('reason')
                    ->label(__('dashboard.audit.labels.reason'))
                    ->limit(50)
                    ->tooltip(fn ($record): ?string => $record['reason'])
                    ->wrap(),
                
                TextColumn::make('fields_rolled_back')
                    ->label(__('dashboard.audit.labels.fields_rolled_back'))
                    ->formatStateUsing(function ($record): string {
                        $fields = $record['fields_rolled_back'] ?? [];
                        return implode(', ', array_slice($fields, 0, 3)) . 
                               (count($fields) > 3 ? '...' : '');
                    })
                    ->wrap(),
                
                TextColumn::make('original_change')
                    ->label(__('dashboard.audit.labels.original_change'))
                    ->formatStateUsing(function ($record): string {
                        $original = $record['original_change'] ?? null;
                        if (!$original) {
                            return __('dashboard.audit.labels.not_available');
                        }
                        
                        return $original['event'] . ' on ' . 
                               \Carbon\Carbon::parse($original['changed_at'])->format('M j, Y');
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label(__('dashboard.audit.labels.period'))
                    ->options([
                        'today' => __('dashboard.audit.periods.today'),
                        'week' => __('dashboard.audit.periods.this_week'),
                        'month' => __('dashboard.audit.periods.this_month'),
                        'quarter' => __('dashboard.audit.periods.this_quarter'),
                        'year' => __('dashboard.audit.periods.this_year'),
                    ])
                    ->default('month'),
                
                SelectFilter::make('model_type')
                    ->label(__('dashboard.audit.labels.model_type'))
                    ->options([
                        'UtilityService' => __('dashboard.audit.models.utility_service'),
                        'ServiceConfiguration' => __('dashboard.audit.models.service_configuration'),
                    ]),
            ])
            ->actions([
                TableAction::make('view_details')
                    ->label(__('dashboard.audit.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('dashboard.audit.rollback_details'))
                    ->modalContent(fn ($record) => view('filament.tenant.modals.rollback-details', [
                        'rollback' => $record,
                        'impactAnalysis' => $this->getImpactAnalysis($record),
                    ]))
                    ->modalWidth('4xl'),
                
                TableAction::make('revert_rollback')
                    ->label(__('dashboard.audit.actions.revert_rollback'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.audit.revert_rollback_confirmation'))
                    ->modalDescription(__('dashboard.audit.revert_rollback_warning'))
                    ->form([
                        Textarea::make('reason')
                            ->label(__('dashboard.audit.labels.revert_reason'))
                            ->required()
                            ->placeholder(__('dashboard.audit.placeholders.revert_reason')),
                    ])
                    ->action(function ($record, array $data) {
                        return $this->revertRollback($record, $data['reason']);
                    })
                    ->visible(fn ($record) => $this->canRevertRollback($record)),
            ])
            ->defaultSort('performed_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('dashboard.audit.no_rollbacks'))
            ->emptyStateDescription(__('dashboard.audit.no_rollbacks_description'))
            ->emptyStateIcon('heroicon-o-arrow-uturn-left');
    }

    /**
     * Get the table query for rollback history.
     */
    private function getTableQuery(): Collection
    {
        $tenantId = auth()->user()->currentTeam->id;
        
        // Get all rollbacks for the tenant
        $rollbacks = collect();
        
        // Get rollback history for UtilityService models
        $rollbackService = app(ConfigurationRollbackService::class);
        $utilityServices = \App\Models\UtilityService::where('tenant_id', $tenantId)->get();
        foreach ($utilityServices as $service) {
            $serviceRollbacks = $rollbackService->getRollbackHistory(
                \App\Models\UtilityService::class,
                $service->id
            );
            
            foreach ($serviceRollbacks as $rollback) {
                $rollback['model_type'] = \App\Models\UtilityService::class;
                $rollback['model_name'] = $service->name;
                $rollbacks->push($rollback);
            }
        }
        
        // Get rollback history for ServiceConfiguration models
        $configurations = \App\Models\ServiceConfiguration::whereHas('property', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->get();
        
        foreach ($configurations as $config) {
            $configRollbacks = $rollbackService->getRollbackHistory(
                \App\Models\ServiceConfiguration::class,
                $config->id
            );
            
            foreach ($configRollbacks as $rollback) {
                $rollback['model_type'] = \App\Models\ServiceConfiguration::class;
                $rollback['model_name'] = $config->utilityService->name ?? 'Unknown Service';
                $rollbacks->push($rollback);
            }
        }
        
        return $rollbacks->sortByDesc('performed_at');
    }

    /**
     * Get rollbackable changes for bulk operations.
     */
    private function getRollbackableChanges(): array
    {
        $tenantId = auth()->user()->currentTeam->id;
        
        $changeTracker = app(UniversalServiceChangeTracker::class);
        $changes = $changeTracker->getTenantChanges(
            tenantId: $tenantId,
            startDate: now()->subDays(7), // Last 7 days
            endDate: now(),
        );
        
        $options = [];
        
        foreach ($changes as $change) {
            if ($change->event !== 'rollback' && $change->event !== 'deleted') {
                $rollbackData = $changeTracker->getConfigurationRollbackData($change->auditId);
                
                if ($rollbackData && $rollbackData['can_rollback']) {
                    $modelType = class_basename($change->modelType);
                    $date = $change->changedAt->format('M j, H:i');
                    $options[$change->auditId] = "{$modelType} - {$change->event} on {$date}";
                }
            }
        }
        
        return $options;
    }

    /**
     * Get rollback history for all models.
     */
    private function getRollbackHistory(): Collection
    {
        return $this->getTableQuery();
    }

    /**
     * Get impact analysis for a rollback.
     */
    private function getImpactAnalysis(array $rollback): array
    {
        // This would typically fetch the impact analysis from the rollback metadata
        // For now, return a basic structure
        return [
            'affected_systems' => ['Billing System', 'Meter Reading System'],
            'warnings' => ['Configuration changes may affect billing calculations'],
            'mitigation_steps' => [
                'Review all active configurations after rollback',
                'Recalculate any pending invoices',
                'Notify affected tenants of changes',
            ],
        ];
    }

    /**
     * Check if a rollback can be reverted.
     */
    private function canRevertRollback(array $rollback): bool
    {
        // Check if the rollback was performed recently (within 24 hours)
        $performedAt = \Carbon\Carbon::parse($rollback['performed_at']);
        
        return $performedAt->diffInHours(now()) <= 24;
    }

    /**
     * Perform bulk rollback operation.
     */
    private function performBulkRollback(array $data): void
    {
        $auditIds = $data['audit_ids'];
        $reason = $data['reason'];
        $userId = auth()->id();
        
        $results = [];
        
        $rollbackService = app(ConfigurationRollbackService::class);
        foreach ($auditIds as $auditId) {
            $result = $rollbackService->performRollback(
                auditLogId: (int) $auditId,
                userId: $userId,
                reason: "Bulk rollback: {$reason}",
                notifyStakeholders: false, // Don't spam notifications for bulk operations
            );
            
            $results[] = $result;
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalCount = count($results);
        
        if ($successCount === $totalCount) {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.bulk_rollback_success', [
                    'count' => $successCount,
                ]))
                ->success()
                ->send();
        } else {
            $failureCount = $totalCount - $successCount;
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.bulk_rollback_partial', [
                    'success' => $successCount,
                    'failed' => $failureCount,
                ]))
                ->warning()
                ->send();
        }
        
        $this->resetTable();
    }

    /**
     * Revert a rollback operation.
     */
    private function revertRollback(array $rollback, string $reason): void
    {
        // To revert a rollback, we need to find the original audit entry
        // and perform another rollback to the state before the rollback
        
        $originalAuditId = $rollback['original_change']['id'] ?? null;
        
        if (!$originalAuditId) {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.revert_failed'))
                ->body(__('dashboard.audit.notifications.original_change_not_found'))
                ->danger()
                ->send();
            return;
        }
        
        // Find the rollback audit entry
        $rollbackAudit = \App\Models\AuditLog::find($rollback['rollback_id']);
        
        if (!$rollbackAudit) {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.revert_failed'))
                ->body(__('dashboard.audit.notifications.rollback_audit_not_found'))
                ->danger()
                ->send();
            return;
        }
        
        // Perform the revert by rolling back to the state before the rollback
        $rollbackService = app(ConfigurationRollbackService::class);
        $result = $rollbackService->performRollback(
            auditLogId: $rollbackAudit->id,
            userId: auth()->id(),
            reason: "Revert rollback: {$reason}",
            notifyStakeholders: true,
        );
        
        if ($result['success']) {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.revert_success'))
                ->success()
                ->send();
            
            $this->resetTable();
        } else {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.revert_failed'))
                ->body(implode(', ', $result['errors'] ?? []))
                ->danger()
                ->send();
        }
    }
}