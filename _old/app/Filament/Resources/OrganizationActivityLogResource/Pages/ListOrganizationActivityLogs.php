<?php

namespace App\Filament\Resources\OrganizationActivityLogResource\Pages;

use App\Filament\Resources\OrganizationActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Support\Facades\Response;

class ListOrganizationActivityLogs extends ListRecords
{
    protected static string $resource = OrganizationActivityLogResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    return $this->exportToCsv();
                })
                ->requiresConfirmation()
                ->modalHeading('Export Activity Logs to CSV')
                ->modalDescription('This will export all filtered activity logs to a CSV file.')
                ->modalSubmitActionLabel('Export'),
            
            Actions\Action::make('export_json')
                ->label('Export JSON')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function () {
                    return $this->exportToJson();
                })
                ->requiresConfirmation()
                ->modalHeading('Export Activity Logs to JSON')
                ->modalDescription('This will export all filtered activity logs to a JSON file.')
                ->modalSubmitActionLabel('Export'),
        ];
    }
    
    protected function exportToCsv()
    {
        $query = $this->getFilteredTableQuery();
        $logs = $query->with(['organization', 'user'])->get();
        
        $filename = 'activity-logs-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Timestamp',
                'Organization',
                'User',
                'Action',
                'Resource Type',
                'Resource ID',
                'IP Address',
                'User Agent',
                'Metadata',
            ]);
            
            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->organization?->name ?? 'N/A',
                    $log->user?->name ?? 'N/A',
                    $log->action,
                    $log->resource_type ? class_basename($log->resource_type) : 'N/A',
                    $log->resource_id ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                    $log->user_agent ?? 'N/A',
                    $log->metadata ? json_encode($log->metadata) : 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    protected function exportToJson()
    {
        $query = $this->getFilteredTableQuery();
        $logs = $query->with(['organization', 'user'])->get();
        
        $filename = 'activity-logs-' . now()->format('Y-m-d-His') . '.json';
        
        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                'organization' => [
                    'id' => $log->organization_id,
                    'name' => $log->organization?->name,
                ],
                'user' => [
                    'id' => $log->user_id,
                    'name' => $log->user?->name,
                ],
                'action' => $log->action,
                'resource' => [
                    'type' => $log->resource_type,
                    'id' => $log->resource_id,
                ],
                'request' => [
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                ],
                'metadata' => $log->metadata,
            ];
        });
        
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        return Response::make(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            200,
            $headers
        );
    }
}
