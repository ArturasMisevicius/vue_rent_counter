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

    /**
     * Get the table query for change history.
     */
    private function getTableQuery(): Collection
    {
        $tenantId = auth()->user()->currentTeam->id;
        
        // Get changes for the last 30 days by default
        $startDate = now()->subDays(30);
        $endDate = now();
        
        $changeTracker = app(UniversalServiceChangeTracker::class);
        return $changeTracker->getTenantChanges(
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
        
        $changeTracker = app(UniversalServiceChangeTracker::class);
        $rollbackData = $changeTracker->getConfigurationRollbackData($record->auditId);
        
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