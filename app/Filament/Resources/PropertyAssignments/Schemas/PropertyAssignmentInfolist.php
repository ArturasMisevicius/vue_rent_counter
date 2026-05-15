<?php

namespace App\Filament\Resources\PropertyAssignments\Schemas;

use App\Models\PropertyAssignment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PropertyAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.relation_resources.property_assignments.fields.organization')),
                TextEntry::make('property.name')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.property'))
                    ->state(fn (PropertyAssignment $record): string => $record->property?->displayName() ?? '—'),
                TextEntry::make('tenant_user_id')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.tenant'))
                    ->numeric(),
                TextEntry::make('unit_area_sqm')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.unit_area_sqm'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('assigned_at')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.assigned_at'))
                    ->dateTime(),
                TextEntry::make('unassigned_at')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.unassigned_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
