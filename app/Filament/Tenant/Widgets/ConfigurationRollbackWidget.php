<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Services\Audit\ConfigurationRollbackService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Configuration Rollback Widget
 *
 * Displays rollback history and provides rollback actions for recent changes.
 */
final class ConfigurationRollbackWidget extends BaseWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('dashboard.audit.labels.performed_at'))
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label(__('dashboard.audit.labels.configuration'))
                    ->formatStateUsing(fn (string $state, AuditLog $record): string => class_basename($state) . " #{$record->auditable_id}")
                    ->badge()
                    ->color('primary'),
                TextColumn::make('user.name')
                    ->label(__('dashboard.audit.labels.performed_by'))
                    ->default(__('dashboard.audit.labels.system')),
                TextColumn::make('notes')
                    ->label(__('dashboard.audit.labels.reason'))
                    ->formatStateUsing(fn (?string $state, AuditLog $record): string => $this->extractRollbackReason($record))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('new_values')
                    ->label(__('dashboard.audit.labels.fields_rolled_back'))
                    ->formatStateUsing(fn (?array $state): string => $this->formatRolledBackFields($state))
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('view_details')
                    ->label(__('dashboard.audit.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('dashboard.audit.rollback_details'))
                    ->modalContent(fn (AuditLog $record) => view('filament.tenant.modals.rollback-details', [
                        'rollback' => $this->toRollbackArray($record),
                        'impactAnalysis' => $this->extractImpactAnalysis($record),
                    ]))
                    ->modalWidth('4xl'),
                Action::make('revert_rollback')
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
                    ->action(fn (AuditLog $record, array $data) => $this->revertRollback($record, (string) $data['reason']))
                    ->visible(fn (AuditLog $record): bool => $this->canRevertRollback($record)),
            ])
            ->headerActions([
                Action::make('rollback_history')
                    ->label(__('dashboard.audit.rollback_history'))
                    ->icon('heroicon-o-clock')
                    ->modalHeading(__('dashboard.audit.rollback_history'))
                    ->modalContent(fn () => view('filament.tenant.modals.rollback-history', [
                        'rollbacks' => $this->getRollbackHistoryRows(),
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
                            ->searchable()
                            ->options($this->getRollbackableChanges())
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('dashboard.audit.labels.rollback_reason'))
                            ->required()
                            ->placeholder(__('dashboard.audit.placeholders.bulk_rollback_reason')),
                    ])
                    ->action(fn (array $data) => $this->performBulkRollback($data))
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.audit.bulk_rollback_confirmation'))
                    ->modalDescription(__('dashboard.audit.bulk_rollback_warning')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('dashboard.audit.no_rollbacks'))
            ->emptyStateDescription(__('dashboard.audit.no_rollbacks_description'))
            ->emptyStateIcon('heroicon-o-arrow-uturn-left');
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
            ->where('event', 'rollback')
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->latest('created_at');
    }

    private function getRollbackHistoryRows()
    {
        return (clone $this->getTableQuery())
            ->limit(100)
            ->get()
            ->map(fn (AuditLog $record): array => $this->toRollbackArray($record));
    }

    private function toRollbackArray(AuditLog $record): array
    {
        $metadata = $record->getAttribute('metadata');
        $metadata = is_array($metadata) ? $metadata : [];

        $originalChange = null;
        $originalAuditId = $metadata['original_audit_id'] ?? null;

        if (is_numeric($originalAuditId)) {
            $original = AuditLog::find((int) $originalAuditId);
            if ($original) {
                $originalChange = [
                    'id' => $original->id,
                    'event' => $original->event,
                    'changed_at' => $original->created_at,
                    'changed_by' => $original->user_id,
                ];
            }
        }

        return [
            'rollback_id' => $record->id,
            'performed_at' => $record->created_at,
            'performed_by' => $record->user_id,
            'model_type' => $record->auditable_type,
            'model_id' => $record->auditable_id,
            'reason' => $this->extractRollbackReason($record),
            'original_change' => $originalChange,
            'fields_rolled_back' => array_keys($record->new_values ?? []),
        ];
    }

    private function extractRollbackReason(AuditLog $record): string
    {
        $metadata = $record->getAttribute('metadata');
        if (is_array($metadata) && filled($metadata['rollback_reason'] ?? null)) {
            return (string) $metadata['rollback_reason'];
        }

        return $record->notes ?: __('dashboard.audit.labels.not_available');
    }

    private function extractImpactAnalysis(AuditLog $record): array
    {
        $metadata = $record->getAttribute('metadata');

        if (! is_array($metadata)) {
            return [];
        }

        $impactAnalysis = $metadata['impact_analysis'] ?? [];

        return is_array($impactAnalysis) ? $impactAnalysis : [];
    }

    private function formatRolledBackFields(?array $newValues): string
    {
        if (empty($newValues)) {
            return __('dashboard.audit.labels.not_available');
        }

        $fields = array_keys($newValues);
        $preview = array_slice($fields, 0, 3);

        return implode(', ', $preview) . (count($fields) > 3 ? '...' : '');
    }

    private function canRevertRollback(AuditLog $record): bool
    {
        $performedAt = $record->created_at instanceof Carbon
            ? $record->created_at
            : Carbon::parse((string) $record->created_at);

        return $performedAt->diffInHours(now()) <= 24;
    }

    private function getRollbackableChanges(): array
    {
        $tenantId = auth()->user()?->currentTeam?->id;

        if (! $tenantId) {
            return [];
        }

        return AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->whereIn('event', ['created', 'updated'])
            ->where('created_at', '>=', now()->subDays(7))
            ->latest('created_at')
            ->limit(100)
            ->get(['id', 'auditable_type', 'event', 'auditable_id', 'created_at'])
            ->mapWithKeys(function (AuditLog $audit): array {
                $modelType = class_basename($audit->auditable_type);
                $when = $audit->created_at->format('M j, H:i');
                $label = "{$modelType} #{$audit->auditable_id} - {$audit->event} ({$when})";

                return [$audit->id => $label];
            })
            ->all();
    }

    private function performBulkRollback(array $data): void
    {
        $auditIds = array_map('intval', $data['audit_ids'] ?? []);
        $reason = (string) ($data['reason'] ?? '');
        $userId = (int) auth()->id();

        if (empty($auditIds) || $userId === 0) {
            Notification::make()
                ->title(__('dashboard.audit.notifications.bulk_rollback_partial', ['success' => 0, 'failed' => 0]))
                ->warning()
                ->send();
            return;
        }

        $rollbackService = app(ConfigurationRollbackService::class);
        $results = [];

        foreach ($auditIds as $auditId) {
            $results[] = $rollbackService->performRollback(
                auditLogId: $auditId,
                userId: $userId,
                reason: "Bulk rollback: {$reason}",
                notifyStakeholders: false,
            );
        }

        $successCount = count(array_filter($results, fn (array $result): bool => (bool) ($result['success'] ?? false)));
        $failureCount = count($results) - $successCount;

        if ($failureCount === 0) {
            Notification::make()
                ->title(__('dashboard.audit.notifications.bulk_rollback_success', ['count' => $successCount]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('dashboard.audit.notifications.bulk_rollback_partial', ['success' => $successCount, 'failed' => $failureCount]))
                ->warning()
                ->send();
        }

        Cache::forget('rollback_history:' . (auth()->user()?->currentTeam?->id ?? 'none'));
        $this->resetTable();
    }

    private function revertRollback(AuditLog $rollbackRecord, string $reason): void
    {
        $userId = (int) auth()->id();

        if ($userId === 0) {
            return;
        }

        $result = app(ConfigurationRollbackService::class)->performRollback(
            auditLogId: $rollbackRecord->id,
            userId: $userId,
            reason: "Revert rollback: {$reason}",
            notifyStakeholders: true,
        );

        if ((bool) ($result['success'] ?? false)) {
            Notification::make()
                ->title(__('dashboard.audit.notifications.revert_success'))
                ->success()
                ->send();

            $this->resetTable();
            return;
        }

        Notification::make()
            ->title(__('dashboard.audit.notifications.revert_failed'))
            ->body(implode(', ', $result['errors'] ?? []))
            ->danger()
            ->send();
    }
}
