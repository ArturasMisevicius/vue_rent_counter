<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Services\Audit\ConfigurationRollbackService;
use App\Services\Audit\UniversalServiceChangeTracker;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Audit Change History Widget
 *
 * Displays audit changes and allows selective rollback operations.
 */
final class AuditChangeHistoryWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('dashboard.audit.labels.changed_at'))
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label(__('dashboard.audit.labels.model_type'))
                    ->formatStateUsing(fn (string $state, AuditLog $record): string => class_basename($state) . " #{$record->auditable_id}")
                    ->badge()
                    ->color('primary'),
                TextColumn::make('event')
                    ->label(__('dashboard.audit.labels.event'))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'rollback' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label(__('dashboard.audit.labels.user'))
                    ->default(__('dashboard.audit.labels.system')),
                TextColumn::make('new_values')
                    ->label(__('dashboard.audit.labels.changed_fields'))
                    ->formatStateUsing(fn (?array $state, AuditLog $record): string => $this->formatChangedFields($record))
                    ->wrap(),
                TextColumn::make('notes')
                    ->label(__('dashboard.audit.labels.notes'))
                    ->limit(80)
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label(__('dashboard.audit.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('dashboard.audit.change_details'))
                    ->modalContent(fn (AuditLog $record) => view('filament.tenant.modals.change-details', [
                        'change' => (object) [
                            'auditId' => $record->id,
                            'modelType' => $record->auditable_type,
                            'modelId' => $record->auditable_id,
                            'event' => $record->event,
                            'userId' => $record->user_id,
                            'changedAt' => $record->created_at,
                            'oldValues' => $record->old_values ?? [],
                            'newValues' => $record->new_values ?? [],
                            'notes' => $record->notes,
                        ],
                        'rollbackData' => app(UniversalServiceChangeTracker::class)->getConfigurationRollbackData($record->id),
                    ]))
                    ->modalWidth('5xl'),
                Action::make('rollback')
                    ->label(__('dashboard.audit.actions.rollback'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (AuditLog $record): bool => $this->canRollback($record))
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.audit.rollback_confirmation'))
                    ->modalDescription(__('dashboard.audit.rollback_warning'))
                    ->form([
                        Textarea::make('reason')
                            ->label(__('dashboard.audit.labels.rollback_reason'))
                            ->required()
                            ->placeholder(__('dashboard.audit.placeholders.rollback_reason')),
                    ])
                    ->action(fn (AuditLog $record, array $data) => $this->performRollback($record, (string) $data['reason'])),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label(__('dashboard.audit.actions.export_csv'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn () => $this->exportChangeHistory()),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('dashboard.audit.no_changes'))
            ->emptyStateDescription(__('dashboard.audit.no_changes_description'))
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = auth()->user()?->currentTeam?->id;

        if (! $tenantId) {
            return AuditLog::query()->whereRaw('1 = 0');
        }

        return AuditLog::query()
            ->with('user:id,name')
            ->where('tenant_id', $tenantId)
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->latest('created_at');
    }

    private function formatChangedFields(AuditLog $record): string
    {
        $fields = array_keys($record->getChanges());

        if (empty($fields)) {
            return __('dashboard.audit.labels.none');
        }

        $preview = array_slice($fields, 0, 3);

        return implode(', ', $preview) . (count($fields) > 3 ? '...' : '');
    }

    private function canRollback(AuditLog $record): bool
    {
        return in_array($record->event, ['created', 'updated'], true);
    }

    private function performRollback(AuditLog $record, string $reason): void
    {
        $userId = (int) auth()->id();

        if ($userId === 0) {
            return;
        }

        $result = app(ConfigurationRollbackService::class)->performRollback(
            auditLogId: $record->id,
            userId: $userId,
            reason: $reason,
            notifyStakeholders: true,
        );

        if ((bool) ($result['success'] ?? false)) {
            Notification::make()
                ->title(__('dashboard.audit.notifications.rollback_success'))
                ->success()
                ->send();

            $this->resetTable();
            return;
        }

        Notification::make()
            ->title(__('dashboard.audit.notifications.rollback_failed'))
            ->body(implode(', ', $result['errors'] ?? []))
            ->danger()
            ->send();
    }

    private function exportChangeHistory()
    {
        $rows = (clone $this->getTableQuery())
            ->limit(1000)
            ->get();

        $filename = 'audit-change-history-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Model Type', 'Event', 'User', 'Changed Fields', 'Notes']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->created_at?->format('Y-m-d H:i:s'),
                    class_basename($row->auditable_type) . " #{$row->auditable_id}",
                    $row->event,
                    $row->user?->name ?? __('dashboard.audit.labels.system'),
                    implode(', ', array_keys($row->getChanges())),
                    $row->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
