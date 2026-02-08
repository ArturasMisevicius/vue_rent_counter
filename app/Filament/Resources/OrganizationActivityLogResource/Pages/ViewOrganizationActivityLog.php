<?php

namespace App\Filament\Resources\OrganizationActivityLogResource\Pages;

use App\Filament\Resources\OrganizationActivityLogResource;
use App\Models\OrganizationActivityLog;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganizationActivityLog extends ViewRecord
{
    protected static string $resource = OrganizationActivityLogResource::class;
    
    protected function getRelatedActions($record): \Illuminate\Support\Collection
    {
        if (!$record->resource_type || !$record->resource_id) {
            return collect();
        }
        
        // Get actions on the same resource within 1 hour window
        return OrganizationActivityLog::query()
            ->where('resource_type', $record->resource_type)
            ->where('resource_id', $record->resource_id)
            ->where('id', '!=', $record->id)
            ->whereBetween('created_at', [
                $record->created_at->copy()->subHour(),
                $record->created_at->copy()->addHour(),
            ])
            ->with(['user', 'organization'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
}
