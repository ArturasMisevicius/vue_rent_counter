<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Audit\UniversalServiceChangeTracker;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Audit Change History Widget
 * 
 * Displays detailed change history for universal services with filtering,
 * search capabilities, and rollback actions.
 */
final class AuditChangeHistoryWidget extends BaseWidget
{
    protected static ?string $heading = null;
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';

    public function __construct(
        private readonly UniversalServiceChangeTracker $changeTracker,
    ) {
        parent::__construct();
    }

    public static function getHeading(): string
    {
        return __('dashboard.audit.change_history');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('dashboard.audit.actions.export_details'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return $this->exportChangeHistory();
                }),
            
            Action::make('refresh')
                ->label(__('dashboard.audit.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('changedAt')
                    ->label(__('dashboard.audit.labels.changed_at'))
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('modelType')
                    ->label(__('dashboard.audit.labels.model_type'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color(fn (string $state): string => match (class_basename($state)) {
                        'UtilityService' => 'primary',
                        'ServiceConfiguration' => 'success',
                        default => 'gray',
                    }),
                
                TextColumn::make('event')
                    ->label(__('dashboard.audit.labels.event'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'rollback' => 'info',
                        default => 'gray',
                    }),
                
                TextColumn::make('userId')
                    ->label(__('dashboard.audit.labels.user'))
                    ->formatStateUsing(function (?int $state): string {
                        if (!$state) {
                            return __('dashboard.audit.labels.system');
                        }
                        
                        $user = \App\Models\User::find($state);
                        return $user ? $user->name : __('dashboard.audit.labels.unknown_user');
                    })
                    ->searchable(),
                
                TextColumn::make('changed_fields')
                    ->label(__('dashboard.audit.labels.changed_fields'))
                    ->formatStateUsing(function ($record): string {
                        $changedFields = array_keys($record->newValues ?? []);
                        return implode(', ', array_slice($changedFields, 0, 3)) . 
                               (count($changedFields) > 3 ? '...' : '');
                    })
                    ->wrap()
                    ->limit(50),
                
                TextColumn::make('notes')
                    ->label(__('dashboard.audit.labels.notes'))
                    ->limit(50)
                    ->tooltip(fn ($record): ?string => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('dashboard.audit.labels.event'))
                    ->options([
                        'created' => __('dashboard.audit.events.created'),
                        'updated' => __('dashboard.audit.events.updated'),
                        'deleted' => __('dashboard.audit.events.deleted'),
                        'rollback' => __('dashboard.audit.events.rollback'),
                    ]),
                
                SelectFilter::make('model_type')
                    ->label(__('dashboard.audit.labels.model_type'))
                    ->options([
                        'App\\Models\\UtilityService' => __('dashboard.audit.models.utility_service'),
                        'App\\Models\\ServiceConfiguration' => __('dashboard.audit.models.service_configuration'),
                    ]),
                
                SelectFilter::make('period')
                    ->label(__('dashboard.audit.labels.period'))
                    ->options([
                        'today' => __('dashboard.audit.periods.today'),
                        'week' => __('dashboard.audit.periods.this_week'),
                        'month' => __('dashboard.audit.periods.this_month'),
                        'quarter' => __('dashboard.audit.periods.this_quarter'),
                    ])
                    ->default('month'),
            ])
            ->actions([
                TableAction::make('view_details')
                    ->label(__('dashboard.audit.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('dashboard.audit.change_details'))
                    ->modalContent(fn ($record) => view('filament.tenant.modals.change-details', [
                        'change' => $record,
                        'rollbackData' => $this->changeTracker->getConfigurationRollbackData($record->auditId),
                    ]))
                    ->modalWidth('4xl'),
                
                TableAction::make('rollback')
                    ->label(__('dashboard.audit.actions.rollback'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.audit.rollback_confirmation'))
                    ->modalDescription(__('dashboard.audit.rollback_warning'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label(__('dashboard.audit.labels.rollback_reason'))
                            ->required()
                            ->placeholder(__('dashboard.audit.placeholders.rollback_reason')),
                    ])
                    ->action(function ($record, array $data) {
                        return $this->performRollback($record, $data['reason']);
                    })
                    ->visible(fn ($record) => $this->canRollback($record)),
            ])
            ->defaultSort('changedAt', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }

    /**
     * Get the table query for change history.
     */
    private function getTableQuery(): Collection
    {
        $tenantId = auth()->user()->currentTeam->id;
        
        // Get changes for the last 30 days by default
        $startDate = now()->subDays(30);
        $endDate = now();
        
        return $this->changeTracker->getTenantChanges(
            tenantId: $tenantId,
            startDate: $startDate,
            endDate: $endDate,
        );
    }

    /**
     * Check if a change can be rolled back.
     */
    private function canRollback($record): bool
    {
        if ($record->event === 'rollback' || $record->event === 'deleted') {
            return false;
        }
        
        $rollbackData = $this->changeTracker->getConfigurationRollbackData($record->auditId);
        
        return $rollbackData && $rollbackData['can_rollback'];
    }

    /**
     * Perform configuration rollback.
     */
    private function performRollback($record, string $reason): void
    {
        $rollbackService = app(\App\Services\Audit\ConfigurationRollbackService::class);
        
        $result = $rollbackService->performRollback(
            auditLogId: $record->auditId,
            userId: auth()->id(),
            reason: $reason,
            notifyStakeholders: true,
        );
        
        if ($result['success']) {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.rollback_success'))
                ->success()
                ->send();
            
            $this->resetTable();
        } else {
            \Filament\Notifications\Notification::make()
                ->title(__('dashboard.audit.notifications.rollback_failed'))
                ->body(implode(', ', $result['errors'] ?? []))
                ->danger()
                ->send();
        }
    }

    /**
     * Export change history to CSV.
     */
    private function exportChangeHistory()
    {
        $changes = $this->getTableQuery();
        
        $csvData = [];
        $csvData[] = [
            'Date',
            'Model Type',
            'Event',
            'User',
            'Changed Fields',
            'Notes',
        ];
        
        foreach ($changes as $change) {
            $user = $change->userId ? 
                (\App\Models\User::find($change->userId)?->name ?? 'Unknown User') : 
                'System';
            
            $csvData[] = [
                $change->changedAt->format('Y-m-d H:i:s'),
                class_basename($change->modelType),
                $change->event,
                $user,
                implode(', ', array_keys($change->newValues ?? [])),
                $change->notes ?? '',
            ];
        }
        
        $filename = 'audit-change-history-' . now()->format('Y-m-d-H-i-s') . '.csv';
        
        return response()->streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}